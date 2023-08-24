<?php

namespace schedule\scheduling;

use think\console\Command as ThinkCommand;

class ScheduleClearCacheCommand extends ThinkCommand
{

    /**
     * Execute the console command.
     *
     * @param Schedule $schedule
     * @return void
     */

    protected function configure()
    {
        $this->setName('schedule:clear-cache')
            ->setDescription('Delete the cached mutex files created by scheduler');
    }

    public function handle()
    {
        $mutexCleared = false;

        $this->schedule = ScheduleConsole::$schedule;

        foreach ($this->schedule->events() as $event) {
            if ($event->mutex->exists($event)) {
                $this->output->info(sprintf('Deleting mutex for [%s]', $event->command));

                $event->mutex->forget($event);

                $mutexCleared = true;
            }
        }

        if (!$mutexCleared) {
            $this->output->info('No mutex files were found.');
        }
    }
}
