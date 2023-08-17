<?php

namespace schedule;

class Service extends \think\Service
{
    public function boot()
    {
        $this->commands(Run::class);
    }
}