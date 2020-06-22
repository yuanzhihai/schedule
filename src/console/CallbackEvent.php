<?php

namespace schedule\console;

use InvalidArgumentException;
use think\Container;

class CallbackEvent extends Event
{
    protected $callback;

    protected $parameters;

    public function __construct($callback, array $parameters = [])
    {
        if (! is_string($callback) && ! is_callable($callback)) {
            throw new InvalidArgumentException(
                'Invalid scheduled callback event. Must be a string or callable.'
            );
        }

        $this->callback = $callback;

        $this->parameters = $parameters;
    }

    /**
     * Run the given event.
     *
     * @param  \think\Container  $container
     * @return mixed
     *
     * @throws \Exception
     */
    public function run(Container $container)
    {
        $this->callBeforeCallbacks($container);

        try {
            $response = $container->invokeFunction($this->callback, $this->parameters);
        } finally {
            parent::callAfterCallbacks($container);
        }

        return $response;
    }
}
