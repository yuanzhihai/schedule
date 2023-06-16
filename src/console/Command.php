<?php


namespace schedule\console;

use think\Queue;
use think\console\Command as ThinkCommand;
use think\Container;
use think\console\Input;
use think\console\Output;

class Command extends ThinkCommand
{
    protected $app;

    protected $events = [];

    public function __construct($name = null)
    {
        parent::__construct( $name );

        $this->app = Container::getInstance();
    }

    public function call($callback,array $parameters = [])
    {
        $this->events[] = $event = new CallbackEvent(
            $callback,$parameters
        );

        return $event;
    }

    public function command($command,array $parameters = [])
    {
        $this->events[] = $event = new Event(
            $command,$parameters
        );

        return $event;
    }

    public function job($job,$data,$queue = null)
    {
        return $this->call( function ($data) use ($job,$queue) {
            Queue::push( $job,$data,$queue );
        },[$data] );
    }

    /*
        $this->command('article:pushed')->dailyAt("21:00");

        /*$this->call(function () use ($input, $output){
            echo '-------------';
            echo 11;
        })->twiceDaily(9, 20);
    */
    protected function execute(Input $input,Output $output)
    {
        $eventsRan = false;

        foreach ( $this->dueEvents( $this->events ) as $event ) {
            if (!$event->filtersPass( $this->app )) {
                continue;
            }
            $event->run( $this->app );

            $eventsRan = true;
        }

        if (!$eventsRan) {
            $output->writeln( 'No scheduled commands are ready to run.' );
        }
    }

    public function dueEvents($app)
    {
        return collect( $this->events )->filter( function ($event) use ($app) {
            return $event->isDue( $app );
        } );
    }
}
