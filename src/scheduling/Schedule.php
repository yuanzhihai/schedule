<?php

namespace schedule\scheduling;

use schedule\Application;
use schedule\ProcessUtils;
use think\Container;
use think\Queue;
use think\queue\Queueable;
use think\queue\ShouldQueue;

class Schedule
{
    /**
     * @var Event[]
     */
    public $events = [];

    const SUNDAY = 0;

    const MONDAY = 1;

    const TUESDAY = 2;

    const WEDNESDAY = 3;

    const THURSDAY = 4;

    const FRIDAY = 5;

    const SATURDAY = 6;


    /**
     * The event mutex implementation.
     *
     * @var EventMutex
     */
    protected $eventMutex;

    /**
     * The scheduling mutex implementation.
     *
     * @var SchedulingMutex
     */
    protected $schedulingMutex;

    /**
     * The timezone the date should be evaluated on.
     *
     * @var \DateTimeZone|string
     */
    protected $timezone;

    /**
     * The cache of mutex results.
     *
     * @var array<string, bool>
     */
    protected $mutexCache = [];

    protected $dispatch;

    public function __construct($timezone = null)
    {
        $this->timezone = $timezone;

        if (!class_exists(Container::class)) {
            throw new RuntimeException(
                'A container implementation is required to use the scheduler. Please install the illuminate/container package.'
            );
        }
        $container = Container::getInstance();

        $this->dispatch = $container->make(Queue::class);

        $this->eventMutex = $container->bound(EventMutex::class)
            ? $container->make(EventMutex::class)
            : $container->make(CacheEventMutex::class);

        $this->schedulingMutex = $container->bound(SchedulingMutex::class)
            ? $container->make(SchedulingMutex::class)
            : $container->make(CacheSchedulingMutex::class);
    }

    /**
     * @param callable $callback
     * @param array $parameters
     * @return CallbackEvent
     */
    public function call(callable $callback, array $parameters = [])
    {
        $this->events[] = $event = new CallbackEvent(
            $this->eventMutex, $callback, $parameters, $this->timezone
        );
        return $event;
    }


    /**
     * @param        $command
     * @param array $parameters
     * @param string $consoleName
     * @return Event
     */
    public function command($command, array $parameters = [])
    {
        if (class_exists($command)) {
            $command = Container::getInstance()->make($command);
            return $this->exec(
                Application::formatCommandString($command->getName()), $parameters
            )->description($command->getDescription());
        }
        return $this->exec(Application::formatCommandString($command), $parameters);
    }

    /**
     * Add a new job callback event to the schedule.
     *
     * @param object|string $job
     * @param string|null $queue
     * @param string|null $connection
     * @return CallbackEvent
     */
    public function job($job, $queue = null, $connection = null)
    {
        return $this->call(function () use ($job, $queue, $connection) {
            $job = is_string($job) ? Container::getInstance()->make($job) : $job;

            if ($job instanceof ShouldQueue) {
                $this->dispatchToQueue($job, $queue ?? $job->queue, $connection ?? $job->connection);
            } else {
                $this->dispatchNow($job,$queue,$connection);
            }
        })->name(is_string($job) ? $job : get_class($job));
    }


    /**
     * @param       $command
     * @param array $parameters
     * @return Event
     */
    public function exec($command, array $parameters = [])
    {
        if (count($parameters)) {
            $command .= ' ' . $this->compileParameters($parameters);
        }
        $this->events[] = $cronEvent = new Event($this->eventMutex, $command, $this->timezone);
        return $cronEvent;
    }

    /**
     * Dispatch the given job to the queue.
     *
     * @param object $job
     * @param string|null $queue
     * @param string|null $connection
     * @return void
     *
     */
    protected function dispatchToQueue($job, $queue, $connection)
    {
        if (in_array(Queueable::class, class_uses_recursive($job))) {
            $dispatch = $this->dispatch->connection($connection);
            if ($job->delay > 0) {
                $dispatch->later($job->delay, $job, '', $queue);
            } else {
                $dispatch->push($job, '', $queue);
            }
        } else {
            $this->dispatch->connection($connection)->push($job,'',$queue);
        }
    }

    /**
     * @param object $job
     * @return void
     */
    protected function dispatchNow($job,$queue,$connection)
    {
        $this->dispatch->connection($connection)->push($job,'',$queue);
    }

    /**
     * Determine if the server is allowed to run this event.
     *
     * @param  Event  $event
     * @param  \DateTimeInterface  $time
     * @return bool
     */
    public function serverShouldRun(Event $event, \DateTimeInterface $time)
    {
        return $this->mutexCache[$event->mutexName()] ??= $this->schedulingMutex->create($event, $time);
    }
    /**
     * Get all of the events on the schedule.
     *
     * @return Event[]
     */
    public function events()
    {
        return $this->events;
    }

    /**
     * @return Event[]
     */
    public function dueEvents()
    {
        return array_filter($this->events, function (Event $event) {
            return $event->isDue();
        });
    }


    /**
     * @return \Generator
     */
    public function dueEventsGenerator()
    {
        foreach ($this->dueEvents() as $event) {
            yield $event;
        }
    }

    /**
     * @param array $parameters
     * @return string
     */
    protected function compileParameters(array $parameters)
    {
        $commandArr = [];
        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                $value = $this->compileArrayInput($key, $value);
            } elseif (!is_numeric($value) && !preg_match('/^(-.$|--.*)/i', $value)) {
                $value = ProcessUtils::escapeArgument($value);
            }
            $commandArr[] = is_numeric($key) ? $value : (strpos($value, $key) !== false ? $value : "{$key}={$value}");
        }
        return implode(' ', $commandArr);
    }


    /**
     * @param       $key
     * @param array $value
     * @return string
     */
    protected function compileArrayInput($key, array $value)
    {
        array_walk($value, function (&$v) {
            $v = ProcessUtils::escapeArgument($v);
        });
        if ($this->startsWith($key, '--')) {
            array_walk($value, function (&$v) use ($key) {
                $v = "{$key}={$v}";
            });
        } elseif ($this->startsWith($key, '-')) {
            array_walk($value, function (&$v) use ($key) {
                $v = "{$key} {$v}";
            });
        }
        return implode(' ', $value);
    }

    /**
     * @param $haystack
     * @param $needles
     * @return bool
     */
    protected function startsWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string)$needle) {
                return true;
            }
        }
        return false;
    }

    public function useCache($store)
    {
        if ($this->eventMutex instanceof CacheAware) {
            $this->eventMutex->useStore($store);
        }

        if ($this->schedulingMutex instanceof CacheAware) {
            $this->schedulingMutex->useStore($store);
        }

        return $this;
    }

}