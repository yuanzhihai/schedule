<?php

namespace schedule;

use schedule\scheduling\ScheduleClearCacheCommand;
use schedule\scheduling\ScheduleInitCommand;
use schedule\scheduling\ScheduleListCommand;
use schedule\scheduling\ScheduleRunCommand;
use schedule\scheduling\ScheduleWorkCommand;
use think\Service;

class ScheduleService extends Service
{

    public function boot()
    {
        // 服务启动
        $this->commands(
            [
                ScheduleClearCacheCommand::class,
                ScheduleInitCommand::class,
                ScheduleListCommand::class,
                ScheduleRunCommand::class,
                ScheduleWorkCommand::class,
            ]
        );
    }
}