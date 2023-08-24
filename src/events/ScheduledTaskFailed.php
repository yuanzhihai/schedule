<?php

namespace schedule\events;

use schedule\scheduling\Event;
use Throwable;

class ScheduledTaskFailed
{
    /**
     * The scheduled event that failed.
     *
     * @var Event
     */
    public $task;

    /**
     * The exception that was thrown.
     *
     * @var \Throwable
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param Event
     * @param \Throwable $exception
     * @return void
     */
    public function __construct(Event $task, Throwable $exception)
    {
        $this->task      = $task;
        $this->exception = $exception;
    }
}
