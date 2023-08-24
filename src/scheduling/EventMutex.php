<?php

namespace schedule\scheduling;

interface EventMutex
{
    /**
     * Attempt to obtain an event mutex for the given event.
     *
     * @param Event $event
     * @return bool
     */
    public function create(Event $event);

    /**
     * Determine if an event mutex exists for the given event.
     *
     * @param Event $event
     * @return bool
     */
    public function exists(Event $event);

    /**
     * Clear the event mutex for the given event.
     *
     * @param Event $event
     * @return void
     */
    public function forget(Event $event);
}
