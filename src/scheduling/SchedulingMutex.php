<?php

namespace schedule\scheduling;

use DateTimeInterface;

interface SchedulingMutex
{
    /**
     * Attempt to obtain a scheduling mutex for the given event.
     *
     * @param  Event  $event
     * @param  \DateTimeInterface  $time
     * @return bool
     */
    public function create(Event $event, DateTimeInterface $time);

    /**
     * Determine if a scheduling mutex exists for the given event.
     *
     * @param  Event  $event
     * @param  \DateTimeInterface  $time
     * @return bool
     */
    public function exists(Event $event, DateTimeInterface $time);
}
