<?php

namespace schedule\events;

use schedule\scheduling\Event;

class ScheduledTaskFinished
{
    /**
     * The scheduled event that ran.
     *
     * @var Event
     */
    public $task;

    /**
     * The runtime of the scheduled event.
     *
     * @var float
     */
    public $runtime;

    /**
     * Create a new event instance.
     *
     * @param Event $task
     * @param float $runtime
     * @return void
     */
    public function __construct(Event $task, $runtime)
    {
        $this->task    = $task;
        $this->runtime = $runtime;
    }
}
