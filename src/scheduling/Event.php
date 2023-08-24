<?php

namespace schedule\scheduling;

use Carbon\Carbon;
use Closure;
use Cron\CronExpression;
use schedule\ProcessUtils;
use Symfony\Component\Process\Process as SymfonyProcess;
use think\Container;
use think\helper\Arr;
use mailer\Mailer;

class Event
{
    use ManagesFrequencies;

    /**
     * @var string
     */
    public $user;

    public $command;

    public $timezone;

    public $expression = '* * * * *';

    /**
     * How often to repeat the event during a minute.
     *
     * @var int|null
     */
    public $repeatSeconds = null;

    /**
     * Indicates if the command should run in maintenance mode.
     *
     * @var bool
     */
    public $evenInMaintenanceMode = false;

    /**
     * Indicates if the command should not overlap itself.
     *
     * @var bool
     */
    public $withoutOverlapping = false;

    /**
     * Indicates if the command should only be allowed to run on one server for each cron expression.
     *
     * @var bool
     */
    public $onOneServer = false;

    /**
     * The number of minutes the mutex should be valid.
     *
     * @var int
     */
    public $expiresAt = 1440;

    /**
     * Indicates if the command should run in the background.
     *
     * @var bool
     */
    public $runInBackground = false;

    protected $filters = [];

    protected $rejects = [];

    /**
     * @var bool
     */
    public $pool = false;
    /**
     * @var int
     */
    public $exitCode;

    protected $beforeCallbacks = [];

    /**
     * @var string
     */
    public $output = '/dev/null';

    /**
     * @var bool
     */
    public $shouldAppendOutput = false;

    /**
     * The event mutex implementation.
     *
     * @var EventMutex
     */
    public $mutex;

    /**
     * The mutex name resolver callback.
     *
     * @var \Closure|null
     */
    public $mutexNameResolver;

    /**
     * The last time the event was checked for eligibility to run.
     *
     * Utilized by sub-minute repeated events.
     *
     * @var Carbon|null
     */
    protected $lastChecked;
    /**
     * The human readable description of the event.
     *
     * @var string|null
     */
    public $description;

    protected $afterCallbacks = [];

    /**
     * @var SymfonyProcess
     */
    public $process;

    public function __construct(EventMutex $mutex, $command, $timezone = null)
    {
        $this->mutex = $mutex;

        $this->command = $command;

        $this->timezone = $timezone;

        $this->output = $this->getDefaultOutput();
    }


    /**
     * @param $user
     * @return $this
     */
    public function user($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    protected function getDefaultOutput()
    {
        return (DIRECTORY_SEPARATOR === '\\') ? 'NUL' : '/dev/null';
    }

    public function run(Container $container)
    {
        if ($this->shouldSkipDueToOverlapping()) {
            return;
        }

        $exitCode = $this->start($container);

        if (!$this->runInBackground) {
            $this->finish($container, $exitCode);
        }
    }

    /**
     * Determine if the event should skip because another process is overlapping.
     *
     * @return bool
     */
    public function shouldSkipDueToOverlapping()
    {
        return $this->withoutOverlapping && !$this->mutex->create($this);
    }

    /**
     * @return $this
     */
    public function pool()
    {
        $this->pool = true;
        return $this;
    }

    protected function start(Container $container)
    {
        try {
            $this->callBeforeCallbacks($container);

            return $this->execute($container);
        } catch (Throwable $exception) {
            $this->removeMutex();
            throw $exception;
        }

    }

    protected function execute(Container $container)
    {
        return $this->pool ? $this->process()->start() : $this->process()->run();
    }

    /**
     * Mark the command process as finished and run callbacks/cleanup.
     *
     * @param int $exitCode
     * @return void
     */
    public function finish(Container $container, $exitCode)
    {
        $this->exitCode = (int)$exitCode;
        try {
            $this->callAfterCallbacks($container);
        } finally {
            $this->removeMutex();
        }
    }

    /**
     * @param $exitCode
     * @return $this
     */
    public function exitCode($exitCode)
    {
        $this->exitCode = $exitCode;
        return $this;
    }


    /**
     * 过滤
     * @param Container $app
     * @return bool
     */
    public function filtersPass(Container $app)
    {
        $this->lastChecked = Carbon::now();
        foreach ($this->filters as $callback) {
            if (!$app->invokeFunction($callback)) {
                return false;
            }
        }
        foreach ($this->rejects as $callback) {
            if ($app->invokeFunction($callback)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 是否到期执行
     * @param $app
     * @return bool
     */
    public function isDue()
    {
        if ($this->runsInMaintenanceMode()) {
            return false;
        }
        return $this->expressionPasses();
    }

    /**
     * Determine if the event runs in maintenance mode.
     *
     * @return bool
     */
    public function runsInMaintenanceMode()
    {
        return $this->evenInMaintenanceMode;
    }

    public function expressionPasses()
    {
        $date = Carbon::now();

        if ($this->timezone) {
            $date->setTimezone($this->timezone);
        }
        return (new CronExpression($this->expression))->isDue($date->toDateTimeString());
    }

    public function skip($callback)
    {
        $this->rejects[] = is_callable($callback) ? $callback : function () use ($callback) {
            return $callback;
        };

        return $this;
    }

    public function when($callback)
    {
        $this->filters[] = is_callable($callback) ? $callback : function () use ($callback) {
            return $callback;
        };

        return $this;
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * The expiration time of the underlying cache lock may be specified in minutes.
     *
     * @param int $expiresAt
     * @return $this
     */
    public function withoutOverlapping($expiresAt = 1440)
    {
        $this->withoutOverlapping = true;

        $this->expiresAt = $expiresAt;

        return $this->skip(function () {
            return $this->mutex->exists($this);
        });
    }

    /**
     * Allow the event to only run on one server for each cron expression.
     *
     * @return $this
     */
    public function onOneServer()
    {
        $this->onOneServer = true;

        return $this;
    }

    /**
     * State that the command should run even in maintenance mode.
     *
     * @return $this
     */
    public function evenInMaintenanceMode()
    {
        $this->evenInMaintenanceMode = true;

        return $this;
    }

    /**
     * @param Closure $callback
     * @return $this
     */
    public function onSuccess(Closure $callback)
    {
        return $this->after(function (Container $container) use ($callback) {
            if (0 === $this->exitCode) {
                $container->invokeFunction($callback);
            }
        });
    }

    /**
     * @param Closure $callback
     * @return $this
     */
    public function onFailure(Closure $callback)
    {
        return $this->after(function (Container $container) use ($callback) {
            if (0 !== $this->exitCode) {
                $container->invokeFunction($callback);
            }
        });
    }

    public function before(Closure $callback)
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }


    public function after(Closure $callback)
    {
        return $this->then($callback);
    }


    public function then(Closure $callback)
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    public function callBeforeCallbacks(Container $container)
    {
        if ($beforeCallbacks = $this->beforeCallbacks) {
            foreach ($beforeCallbacks as $callback) {
                $container->invokeFunction($callback);
            }
        }
    }

    public function callAfterCallbacks(Container $container)
    {
        if ($afterCallbacks = $this->afterCallbacks) {
            foreach ($afterCallbacks as $callback) {
                $container->invokeFunction($callback);
            }
        }
    }

    /**
     * Set the human-friendly description of the event.
     *
     * @param string $description
     * @return $this
     */
    public function name($description)
    {
        return $this->description($description);
    }

    /**
     * Set the human-friendly description of the event.
     *
     * @param string $description
     * @return $this
     */
    public function description($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function mutexName()
    {
        $mutexNameResolver = $this->mutexNameResolver;

        if (!is_null($mutexNameResolver) && is_callable($mutexNameResolver)) {
            return $mutexNameResolver($this);
        }
        return 'framework'.DIRECTORY_SEPARATOR.'schedule-' . sha1($this->expression . $this->command);
    }

    public function getDescription()
    {
        return $this->description ?: $this->mutexName();
    }

    /**
     * @param $location
     * @param $append
     * @return $this
     */
    public function sendOutputTo($location, $append = false)
    {
        $this->output             = $location;
        $this->shouldAppendOutput = $append;
        return $this;
    }

    /**
     * @param $location
     * @return $this
     */
    public function appendOutputTo($location)
    {
        return $this->sendOutputTo($location, true);
    }

    /**
     * @return $this
     */
    public function storeOutput()
    {
        $this->ensureOutputIsBeingCaptured();

        return $this;
    }

    public function ensureOutputIsBeingCaptured()
    {
        if (is_null($this->output) || $this->output == $this->getDefaultOutput()) {
            $this->sendOutputTo(runtime_path() . 'schedule-' . sha1($this->mutexName()) . '.log');
        }
    }

    public function emailOutputTo($addresses, $onlyIfOutputExists = false)
    {
        $this->ensureOutputIsBeingCaptured();

        $addresses = Arr::wrap($addresses);

        return $this->then(function (Mailer $mailer) use ($addresses, $onlyIfOutputExists) {
            $this->emailOutput($mailer, $addresses, $onlyIfOutputExists);
        });
    }

    /**
     * E-mail the results of the scheduled operation if it produces output.
     *
     * @param array|mixed $addresses
     * @return $this
     */
    public function emailWrittenOutputTo($addresses)
    {
        return $this->emailOutputTo($addresses, true);
    }


    /**
     * E-mail the results of the scheduled operation if it fails.
     *
     * @param array|mixed $addresses
     * @return $this
     */
    public function emailOutputOnFailure($addresses)
    {
        $this->ensureOutputIsBeingCaptured();

        $addresses = Arr::wrap($addresses);

        return $this->onFailure(function (Mailer $mailer) use ($addresses) {
            $this->emailOutput($mailer, $addresses, false);
        });
    }

    /**
     * E-mail the output of the event to the recipients.
     *
     * @param Mailer $mailer
     * @param array $addresses
     * @param bool $onlyIfOutputExists
     * @return void
     */
    protected function emailOutput(Mailer $mailer, $addresses, $onlyIfOutputExists = false)
    {
        $text = is_file($this->output) ? file_get_contents($this->output) : '';

        if ($onlyIfOutputExists && empty($text)) {
            return;
        }
        $mailer->send(function ($mailer, $message) use ($addresses, $text) {
            $mailer->to($addresses)
                ->subject($this->getEmailSubject())
                ->text($text);
        });
    }

    /**
     * Get the e-mail subject line for output results.
     *
     * @return string
     */
    protected function getEmailSubject()
    {
        if ($this->description) {
            return $this->description;
        }

        return "Scheduled Job Output For [{$this->command}]";
    }

    /**
     * Determine the next due date for an event.
     *
     * @param \DateTimeInterface|string $currentTime
     * @param int $nth
     * @param bool $allowCurrentDate
     * @return \Carbon\Carbon
     */
    public function nextRunDate($currentTime = 'now', $nth = 0, $allowCurrentDate = false)
    {
        return Carbon::make((new CronExpression($this->getExpression()))
            ->getNextRunDate($currentTime, $nth, $allowCurrentDate, $this->timezone));
    }

    /**
     * Get the Cron expression for the event.
     *
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * @return SymfonyProcess
     */
    public function process()
    {
        return ProcessUtils::newProcess($this->buildCommand(), (defined('BASE_PATH') ? BASE_PATH : getcwd()));
    }

    /**
     * @return string
     */
    public function getSummaryForDisplay()
    {
        if (is_string($this->description)) {
            return $this->description;
        }
        return $this->buildCommand();
    }

    /**
     * Determine if the event has been configured to repeat multiple times per minute.
     *
     * @return bool
     */
    public function isRepeatable()
    {
        return !is_null($this->repeatSeconds);
    }

    /**
     * Determine if the event is ready to repeat.
     *
     * @return bool
     */
    public function shouldRepeatNow()
    {
        return $this->isRepeatable() && $this->lastChecked?->diffInSeconds() >= $this->repeatSeconds;
    }

    /**
     * State that the command should run in the background.
     *
     * @return $this
     */
    public function runInBackground()
    {
        $this->runInBackground = true;

        return $this;
    }


    public function timezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return string
     */
    public function buildCommand()
    {
        return (new CommandBuilder)->buildCommand($this);
    }

    /**
     * Set the mutex name or name resolver callback.
     *
     * @param  \Closure|string  $mutexName
     * @return $this
     */
    public function createMutexNameUsing(Closure|string $mutexName)
    {
        $this->mutexNameResolver = is_string($mutexName) ? fn () => $mutexName : $mutexName;

        return $this;
    }

    /**
     * Delete the mutex for the event.
     *
     * @return void
     */
    protected function removeMutex()
    {
        if ($this->withoutOverlapping) {
            $this->mutex->forget($this);
        }
    }
}
