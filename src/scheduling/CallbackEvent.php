<?php

namespace schedule\scheduling;

use InvalidArgumentException;
use schedule\exceptions\Exception;
use think\Container;

class CallbackEvent extends Event
{
    protected $callback;

    protected $parameters;

    /**
     * The result of the callback's execution.
     *
     * @var mixed
     */
    protected $result;

    /**
     * The exception that was thrown when calling the callback, if any.
     *
     * @var \Throwable|null
     */
    protected $exception;

    public function __construct(EventMutex $mutex, $callback, array $parameters = [], $timezone = null)
    {
        if (!is_string($callback) && !is_callable($callback)) {
            throw new InvalidArgumentException(
                'Invalid scheduled callback event. Must be a string or callable.'
            );
        }
        $this->mutex      = $mutex;
        $this->callback   = $callback;
        $this->parameters = $parameters;
        $this->timezone   = $timezone;

    }

    /**
     * Run the given event.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function run(Container $container)
    {
        parent::run($container);
        if ($this->exception) {
            throw $this->exception;
        }
        return $this->result;
    }

    /**
     * @param Container $container
     * @return int
     */
    protected function execute(Container $container)
    {
        try {
            $this->result = is_object($this->callback)
                ? $container->invokeMethod([$this->callback, '__invoke'], $this->parameters)
                : $container->invokeFunction($this->callback, $this->parameters);

            return $this->result === false ? 1 : 0;
        } catch (\Throwable $e) {
            $this->exception = $e;

            return 1;
        }
    }


    /**
     * Determine if the event should skip because another process is overlapping.
     *
     * @return bool
     */
    public function shouldSkipDueToOverlapping()
    {
        return $this->description && parent::shouldSkipDueToOverlapping();
    }


    /**
     * Get the summary of the event for display.
     *
     * @return string
     */
    public function getSummaryForDisplay()
    {
        if (is_string($this->description)) {
            return $this->description;
        }

        return is_string($this->callback) ? $this->callback : 'Callback';
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * The expiration time of the underlying cache lock may be specified in minutes.
     *
     * @param int $expiresAt
     * @return $this
     *
     * @throws  Exception
     */
    public function withoutOverlapping($expiresAt = 1440)
    {
        if (!isset($this->description)) {
            throw new Exception (
                "A scheduled event name is required to prevent overlapping. Use the 'name' method before 'withoutOverlapping'."
            );
        }

        return parent::withoutOverlapping($expiresAt);
    }

    /**
     * Get the mutex name for the scheduled command.
     *
     * @return string
     */
    public function mutexName()
    {
        return 'schedule-' . sha1($this->description ?? '');
    }

    /**
     * Clear the mutex for the event.
     *
     * @return void
     */
    protected function removeMutex()
    {
        if ($this->description) {
            parent::removeMutex();
        }
    }
}
