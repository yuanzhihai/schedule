<?php

namespace schedule\scheduling;

use Carbon\Carbon;
use Cron\CronExpression;
use schedule\Application;
use think\console\Command as ThinkCommand;
use think\console\input\Option;
use think\console\Table;

class ScheduleListCommand extends ThinkCommand
{

    protected function configure()
    {
        $this->setName('schedule:list')
            ->setDescription('List the scheduled commands')
            ->addOption('timezone',null,Option::VALUE_OPTIONAL,'The timezone that times should be displayed in');
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $schedule = ScheduleConsole::$schedule;
        $events   = collect($schedule->events());
        if ($events->isEmpty()) {
            $this->output->info('No scheduled tasks have been defined.');
            return;
        }

        $timezone = new \DateTimeZone($this->input->getOption('timezone') ?? config('app.default_timezone'));

        foreach ($events as $event) {

            $command     = $event->command ?? '';

            $command = str_replace([Application::phpBinary(), Application::artisanBinary()], [
                'php',
                preg_replace("#['\"]#", '', Application::artisanBinary()),
            ], $command);

            if ($event instanceof CallbackEvent) {
                $command = $event->getSummaryForDisplay();
                if (in_array($command, ['Closure', 'Callback'])) {
                    $command = 'Closure at: ' . $this->getClosureLocation($event);
                }
            }

            $nextDueDateLabel = 'Next Due:';

            $command = mb_strlen($command) > 1 ? "{$command} " : '';

            $repeatExpressionSpacing = $this->getRepeatExpression($event);

            $nextDueDate = $this->getNextDueDateForEvent($event, $timezone);

            $dots = str_repeat('.', max(
                150 - mb_strlen($event->expression.$repeatExpressionSpacing.$command.$nextDueDateLabel.$nextDueDate) - 8, 0
            ));
            $this->output->writeln('<fg=yellow>'.$event->expression . $repeatExpressionSpacing.'</>  '.$command.$dots.$nextDueDateLabel.$nextDueDate->diffForHumans());
        }
    }

    private function getClosureLocation(CallbackEvent $event)
    {
        $callback = (new \ReflectionClass($event))->getProperty('callback')->getValue($event);

        if ($callback instanceof \Closure) {
            $function = new \ReflectionFunction($callback);

            return sprintf(
                '%s:%s',
                str_replace(base_path(), '', $function->getFileName() ?: ''),
                $function->getStartLine()
            );
        }

        if (is_string($callback)) {
            return $callback;
        }

        if (is_array($callback)) {
            $className = is_string($callback[0]) ? $callback[0] : $callback[0]::class;

            return sprintf('%s::%s', $className, $callback[1]);
        }

        return sprintf('%s::__invoke', $callback::class);
    }

    private function getRepeatExpression($event)
    {
        return $event->isRepeatable() ? " {$event->repeatSeconds}s " : '';
    }

    /**
     * Get the next due date for an event.
     *
     * @param Event $event
     * @param \DateTimeZone $timezone
     * @return Carbon
     */
    private function getNextDueDateForEvent($event, \DateTimeZone $timezone)
    {
        $nextDueDate = Carbon::instance(
            (new CronExpression($event->expression))
                ->getNextRunDate(Carbon::now()->setTimezone($event->timezone))
                ->setTimezone($timezone)
        );

        if (!$event->isRepeatable()) {
            return $nextDueDate;
        }

        $previousDueDate = Carbon::instance(
            (new CronExpression($event->expression))
                ->getPreviousRunDate(Carbon::now()->setTimezone($event->timezone), allowCurrentDate: true)
                ->setTimezone($timezone)
        );

        $now = Carbon::now()->setTimezone($event->timezone);

        if (!$now->copy()->startOfMinute()->eq($previousDueDate)) {
            return $nextDueDate;
        }

        return $now
            ->endOfSecond()
            ->ceilSeconds($event->repeatSeconds);
    }

}
