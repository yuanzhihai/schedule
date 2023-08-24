<?php

namespace schedule\events;

use schedule\scheduling\Event;

class ScheduledTaskSkipped
{
    /**
     * The scheduled event being run.
     *
     * @var Event
     */
    public $task;

    /**
     * Create a new event instance.
     *
     * @param  Event $task
     * @return void
     */
    public function __construct(Event $task)
    {
        $this->task = $task;
    }
}
