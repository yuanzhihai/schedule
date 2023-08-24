<?php

namespace schedule\scheduling;


use think\Cache;

class CacheEventMutex implements EventMutex,CacheAware
{
    /**
     * The cache repository implementation.
     *
     * @var  Cache
     */
    public $cache;

    /**
     * The cache store that should be used.
     *
     * @var string|null
     */
    public $store;

    /**
     * Create a new overlapping strategy.
     *
     * @param  Cache $cache
     * @return void
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to obtain an event mutex for the given event.
     *
     * @param  Event  $event
     * @return bool
     */
    public function create(Event $event)
    {

        return $this->cache->store($this->store)->set(
            $event->mutexName(), true, $event->expiresAt * 60
        );
    }

    /**
     * Determine if an event mutex exists for the given event.
     *
     * @param  Event  $event
     * @return bool
     */
    public function exists(Event $event)
    {
        return $this->cache->store($this->store)->has($event->mutexName());
    }

    /**
     * Clear the event mutex for the given event.
     *
     * @param  Event  $event
     * @return void
     */
    public function forget(Event $event)
    {

        $this->cache->store($this->store)->delete($event->mutexName());
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
