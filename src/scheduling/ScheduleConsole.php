<?php


namespace schedule\scheduling;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use think\facade\App;
use think\helper\Str;


class ScheduleConsole
{

    /**
     * @var Schedule
     */
    public static $schedule;


    /**
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

    }

    /**
     * @return void
     */
    protected function bootstrap()
    {
        $this->defineSchedule();
    }

    /**
     * @return $this
     */
    protected function defineSchedule()
    {
        $schedule = new Schedule();
        $this->schedule($schedule);
        static::$schedule = $schedule;
        return $this;
    }


    public function handle()
    {
        $this->bootstrap();
    }
}