<?php

namespace schedule\scheduling;

use DateTimeInterface;
use think\Cache;

class CacheSchedulingMutex implements SchedulingMutex, CacheAware
{
    /**
     * The cache factory implementation.
     *
     * @var Cache
     */
    public $cache;

    /**
     * The cache store that should be used.
     *
     * @var string|null
     */
    public $store;

    /**
     * Create a new scheduling strategy.
     *
     * @param  Cache
     * @return void
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to obtain a scheduling mutex for the given event.
     *
     * @param  Event  $event
     * @param  \DateTimeInterface  $time
     * @return bool
     */
    public function create(Event $event, DateTimeInterface $time)
    {
        return $this->cache->store($this->store)->set(
            $event->mutexName().$time->format('Hi'), true, 3600
        );
    }

    /**
     * Determine if a scheduling mutex exists for the given event.
     *
     * @param  Event  $event
     * @param  \DateTimeInterface  $time
     * @return bool
     */
    public function exists(Event $event, DateTimeInterface $time)
    {
        return $this->cache->store($this->store)->has(
            $event->mutexName().$time->format('Hi')
        );
    }

    /**
     * Specify the cache store that should be used.
     *
     * @param  string  $store
     * @return $this
     */
    public function useStore($store)
    {
        $this->store = $store;

        return $this;
    }
}
