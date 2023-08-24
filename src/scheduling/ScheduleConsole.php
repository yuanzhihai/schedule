<?php


namespace schedule\scheduling;

use think\App;


class ScheduleConsole
{

    /**
     * @var Schedule
     */
    public static $schedule;

    protected $app;

    public function __construct(App $app)
    {
        if (!defined('THINK_BINARY')) {
            define('THINK_BINARY', 'think');
        }
        $this->app = $app;
    }


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
        $schedule = new Schedule($this->scheduleTimezone());
        $schedule->useCache($this->scheduleCache());
        $this->schedule($schedule);
        static::$schedule = $schedule;
        return $this;
    }

    protected function scheduleTimezone()
    {
        return $this->app->config->get('app.default_timezone');
    }

    protected function scheduleCache()
    {
        return $this->app->config->get('cache.default', 'file');
    }


    public function handle()
    {
        $this->bootstrap();
    }
}