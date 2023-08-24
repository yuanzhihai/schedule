<?php


namespace schedule\scheduling;


use think\console\Command as ThinkCommand;

class ScheduleInitCommand extends ThinkCommand
{

    protected function configure()
    {
        $this->setName('schedule:init')
            ->setDescription('schedule project init');
    }

    /**
     * @return void
     */
    public function handle()
    {
        $this->initfile();
        $this->output->info('init success');
    }

    /**
     * @return void
     */
    protected function initfile()
    {
        $php = '<?php
namespace app;
use schedule\scheduling\ScheduleConsole;
use schedule\scheduling\Schedule;
class ConsoleScheduling extends ScheduleConsole
{
    /**
     * 定义任务计划
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->exec("echo shell")->everyMinute(); 
        $schedule->call(function(){
            file_put_contents(runtime_path()."console-scheduling.log",time().PHP_EOL,FILE_APPEND);
        })->everyMinute(); 
    }
}';
        if (!is_dir('./app')) {
            mkdir('./app');
        }
        file_put_contents('./app/ConsoleScheduling.php', $php);
    }
}
