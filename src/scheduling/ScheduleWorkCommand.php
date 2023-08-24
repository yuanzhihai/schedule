<?php


namespace schedule\scheduling;


use Carbon\Carbon;
use schedule\Application;
use think\console\Command as ThinkCommand;
use schedule\ProcessUtils;
use think\console\input\Option;

class ScheduleWorkCommand extends ThinkCommand
{

    public function configure()
    {
        $this->setName('schedule:work')
            ->addOption('run-output-file', null, Option::VALUE_OPTIONAL, 'The file to direct <info>schedule:run</info> output to')
            ->setDescription('Start the schedule worker');
    }


    /**
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        $this->output->info('Schedule worker started successfully.');
        [$lastExecutionStartedAt, $executions] = [Carbon::now()->subMinutes(10), []];

        $command = Application::formatCommandString('schedule:run');
        if ($this->input->getOption('run-output-file')) {
            $command .= ' >> ' . ProcessUtils::escapeArgument($this->input->getOption('run-output-file')) . ' 2>&1';
        }
        while (true) {
            usleep(100 * 1000);
            if (Carbon::now()->second === 0 &&
                !Carbon::now()->startOfMinute()->equalTo($lastExecutionStartedAt)) {
                $executions[] = $execution = ProcessUtils::newProcess($command);
                $execution->run();
                $lastExecutionStartedAt = Carbon::now()->startOfMinute();
            }
            foreach ($executions as $key => $execution) {
                $output = $execution->getIncrementalOutput() .
                    $execution->getIncrementalErrorOutput();

                $this->output->write(ltrim($output, "\n"));

                if (!$execution->isRunning()) {
                    unset($executions[$key]);
                }
            }
        }
    }
}
