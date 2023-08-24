<?php


namespace schedule\scheduling;


use Carbon\Carbon;
use schedule\Application;
use schedule\events\ScheduledTaskFailed;
use schedule\events\ScheduledTaskFinished;
use schedule\events\ScheduledTaskSkipped;
use schedule\events\ScheduledTaskStarting;
use Symfony\Component\Process\Process;
use think\console\Command as ThinkCommand;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Container;

class ScheduleRunCommand extends ThinkCommand
{
    /**
     * @var Process
     */
    protected $running = [];
    /**
     * @var Schedule
     */
    protected $schedule;

    /**
     * @var  Container
     */
    protected $container;
    /**
     * @var bool
     */
    protected $eventsRan = false;

    /**
     * The 24 hour timestamp this scheduler command started running.
     *
     * @var \Carbon\Carbon
     */
    protected $startedAt;

    protected function configure()
    {
        $this->setName('schedule:run')
            ->setDescription('Run the scheduled commands')
            ->addOption('pool', null, Option::VALUE_OPTIONAL, 'schedule run for pool process')
            ->addOption('size', null, Option::VALUE_OPTIONAL, 'The number of events to process running');
    }

    public function __construct()
    {
        $this->startedAt = Carbon::now();
        parent::__construct();
    }

    public function handle()
    {
        $this->schedule  = ScheduleConsole::$schedule;
        $this->container = Container::getInstance();
        $pool            = $this->input->getOption('pool');
        if ($pool) {
            $this->poolProcess($this->container);
        } else {
            $this->start($this->container);
        }
        if (!$this->eventsRan) {
            $this->output->info('No scheduled commands are ready to run.');
        }
    }

    /**
     * @return void
     */
    protected function start(Container $container)
    {
        foreach ($this->schedule->dueEvents() as $event) {
            if (!$event->filtersPass($container)) {
                $this->app->event->trigger(new ScheduledTaskSkipped($event));
                continue;
            }
            if ($event->onOneServer) {
                $this->runSingleServerEvent($event);
            } else {
                if ($event->isRepeatable()) {
                    $this->repeatEvents($event);
                } else {
                    $this->runEvent($event);
                }
            }
        }
    }

    /**
     * Run the given single server event.
     *
     * @param Event $event
     * @return void
     */
    protected function runSingleServerEvent($event)
    {
        if ($this->schedule->serverShouldRun($event, $this->startedAt)) {
            $this->runEvent($event);
        } else {
            $this->output->info(sprintf(
                'Skipping [%s], as command already run on another server.', $event->getSummaryForDisplay()
            ));
        }
    }

    protected function runEvent($event)
    {
        $summary = $event->getSummaryForDisplay();
        $command = $event instanceof CallbackEvent
            ? $summary
            : trim(str_replace(Application::phpBinary(), '', $event->command));

        $description = sprintf(
            '<fg=cyan>%s</> Running [%s]%s',
            Carbon::now()->format('Y-m-d H:i:s'),
            $command,
            $event->runInBackground ? ' in background' : '',
        );
        $this->app->event->trigger(new ScheduledTaskStarting($event));
        $start = microtime(true);
        try {
            $event->run($this->container);

            $this->app->event->trigger(new ScheduledTaskFinished(
                $event,
                round(microtime(true) - $start, 2)
            ));

            $running_time = round(microtime(true) - $start, 2);
            $dots         = str_repeat('.', max(150 - mb_strlen($description . $command . $running_time) - 8, 0));
            $this->output->writeln($description . $dots . $running_time . 'ms <info>DONE</info>');
            $this->eventsRan = true;
        } catch (\Throwable $e) {
            $this->app->event->trigger(new ScheduledTaskFailed($event, $e));
            $this->output->error($e->getMessage() . ' ' . $e->getTraceAsString());
        }
        if (!$event instanceof CallbackEvent) {
            $this->output->writeln($event->getSummaryForDisplay());
        }
        return $event->exitCode == 0;
    }

    protected function repeatEvents(Event $event)
    {
        while (Carbon::now()->lte($this->startedAt->endOfMinute())) {
            if (!$event->shouldRepeatNow()) {
                continue;
            }
            if ($event->runsInMaintenanceMode()) {
                continue;
            }
            if (!$event->filtersPass($this->container)) {
                $this->app->event->trigger(new ScheduledTaskSkipped());
                continue;
            }
            if ($event->onOneServer) {
                $this->runSingleServerEvent($event);
            } else {
                $this->runEvent($event);
            }
            $this->eventsRan = true;
        }
        usleep(100000);
    }

    /**
     * @return void
     */
    protected function poolProcess(Container $container)
    {
        $this->events = $this->schedule->dueEventsGenerator();
        $this->output->info('[' . date('c') . '] Running scheduled command for Pool');
        $this->startNextProcesses($container);
        while (count($this->running) > 0) {
            /** @var $event Event */
            foreach ($this->running as $index => $event) {
                try {
                    $process = $event->process;
                    $process && $process->checkTimeout();
                    $isRunning = !is_null($process) ? $process->isRunning() : false;
                    if (!$isRunning) {
                        $process && $event->exitCode($process->getExitCode());
                        unset($this->running[$index]);
                        $this->startNextProcesses($container);
                    }
                } catch (\Exception|\Throwable $e) {
                    $this->output->error($e->getMessage());
                }
            }
            usleep(1000);
        }
    }

    /**
     * @return void
     */
    private function startNextProcesses(Container $container)
    {

        $poolSize = $this->input->getOption('size');
        while (count($this->running) < $poolSize && $this->events->valid()) {
            /** @var $event Event */
            $event = $this->events->current();
            if (!$event->filtersPass($container)) {
                $this->app->event->trigger(new ScheduledTaskSkipped($event));
                continue;
            }
            $event->pool();
            if ($event->onOneServer) {
                $this->runSingleServerEvent($event);
            } else {
                if ($event->isRepeatable()) {
                    $this->repeatEvents($event);
                } else {
                    $this->runEvent($event);
                }
            }
            $this->running[] = $event;
            $this->events->next();
        }
    }
}
