<?php

namespace schedule\scheduling;

use Carbon\Carbon;

trait ManagesFrequencies
{
    /**
     * The Cron expression representing the event's frequency.
     *
     * @param string $expression
     * @return $this
     */
    public function cron($expression)
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * 设置区间时间
     * Schedule the event to run between start and end time.
     *
     * @param string $startTime
     * @param string $endTime
     * @return $this
     */
    public function between($startTime, $endTime)
    {
        return $this->when($this->inTimeInterval($startTime, $endTime));
    }

    /**
     * 排除区间时间
     * Schedule the event to not run between start and end time.
     *
     * @param string $startTime
     * @param string $endTime
     * @return $this
     */
    public function unlessBetween($startTime, $endTime)
    {
        return $this->skip($this->inTimeInterval($startTime, $endTime));
    }

    /**
     * Schedule the event to run between start and end time.
     *
     * @param string $startTime
     * @param string $endTime
     * @return \Closure
     */
    private function inTimeInterval($startTime, $endTime)
    {
        [$now, $startTime, $endTime] = [
            Carbon::now($this->timezone),
            Carbon::parse($startTime, $this->timezone),
            Carbon::parse($endTime, $this->timezone),
        ];

        if ($endTime->lessThan($startTime)) {
            if ($startTime->greaterThan($now)) {
                $startTime->subDay(1);
            } else {
                $endTime->addDay(1);
            }
        }

        return function () use ($now, $startTime, $endTime) {
            return $now->between($startTime, $endTime);
        };
    }

    /**
     * Schedule the event to run every second.
     *
     * @return $this
     */
    public function everySecond()
    {
        return $this->repeatEvery(1);
    }

    /**
     * Schedule the event to run every two seconds.
     *
     * @return $this
     */
    public function everyTwoSeconds()
    {
        return $this->repeatEvery(2);
    }

    /**
     * Schedule the event to run every five seconds.
     *
     * @return $this
     */
    public function everyFiveSeconds()
    {
        return $this->repeatEvery(5);
    }

    /**
     * Schedule the event to run every ten seconds.
     *
     * @return $this
     */
    public function everyTenSeconds()
    {
        return $this->repeatEvery(10);
    }

    /**
     * Schedule the event to run every fifteen seconds.
     *
     * @return $this
     */
    public function everyFifteenSeconds()
    {
        return $this->repeatEvery(15);
    }

    /**
     * Schedule the event to run every twenty seconds.
     *
     * @return $this
     */
    public function everyTwentySeconds()
    {
        return $this->repeatEvery(20);
    }

    /**
     * Schedule the event to run every thirty seconds.
     *
     * @return $this
     */
    public function everyThirtySeconds()
    {
        return $this->repeatEvery(30);
    }

    /**
     * Schedule the event to run multiple times per minute.
     *
     * @param int $seconds
     * @return $this
     */
    protected function repeatEvery($seconds)
    {
        if (60 % $seconds !== 0) {
            throw new InvalidArgumentException("The seconds [$seconds] are not evenly divisible by 60.");
        }

        $this->repeatSeconds = $seconds;

        return $this->everyMinute();
    }

    /**
     * 每分钟执行
     * Schedule the event to run every minute.
     *
     * @return $this
     */
    public function everyMinute()
    {
        return $this->spliceIntoPosition(1, '*');
    }

    /**
     * 每两分钟执行
     *
     * @return $this
     */
    public function everyTwoMinutes()
    {
        return $this->spliceIntoPosition(1, '*/2');
    }

    /**
     * 每三分钟执行
     *
     * @return $this
     */
    public function everyThreeMinutes()
    {
        return $this->spliceIntoPosition(1, '*/3');
    }

    /**
     * 每四分钟执行
     *
     * @return $this
     */
    public function everyFourMinutes()
    {
        return $this->spliceIntoPosition(1, '*/4');
    }

    /**
     * 每5分钟执行
     *
     * @return $this
     */
    public function everyFiveMinutes()
    {
        return $this->spliceIntoPosition(1, '*/5');
    }

    /**
     * 每10分钟执行
     * Schedule the event to run every ten minutes.
     *
     * @return $this
     */
    public function everyTenMinutes()
    {
        return $this->spliceIntoPosition(1, '*/10');
    }

    /**
     * 每15分钟执行
     * Schedule the event to run every fifteen minutes.
     *
     * @return $this
     */
    public function everyFifteenMinutes()
    {
        return $this->spliceIntoPosition(1, '*/15');
    }

    /**
     * 每30分钟执行
     * Schedule the event to run every thirty minutes.
     *
     * @return $this
     */
    public function everyThirtyMinutes()
    {
        return $this->spliceIntoPosition(1, '0,30');
    }

    /**
     * 每小时执行
     * Schedule the event to run hourly.
     *
     * @return $this
     */
    public function hourly()
    {
        return $this->spliceIntoPosition(1, 0);
    }

    /**
     * 按小时延期执行
     * Schedule the event to run hourly at a given offset in the hour.
     *
     * @param int $offset
     * @return $this
     */
    public function hourlyAt($offset)
    {
        return $this->spliceIntoPosition(1, $offset);
    }

    /**
     * 每两小时执行
     *
     * @return $this
     */
    public function everyTwoHours()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, '*/2');
    }

    /**
     * 每三小时执行
     *
     * @return $this
     */
    public function everyThreeHours()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, '*/3');
    }

    /**
     * 每四小时执行
     *
     * @return $this
     */
    public function everyFourHours()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, '*/4');
    }

    /**
     * 每六小时执行
     *
     * @return $this
     */
    public function everySixHours()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, '*/6');
    }

    /**
     * 按天执行
     * Schedule the event to run daily.
     *
     * @return $this
     */
    public function daily()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0);
    }

    /**
     * 指定时间执行
     * Schedule the command at a given time.
     *
     * @param string $time
     * @return $this
     */
    public function at($time)
    {
        return $this->dailyAt($time);
    }

    /**
     * 指定时间执行
     * Schedule the event to run daily at a given time (10:00, 19:30, etc).
     *
     * @param string $time
     * @return $this
     */
    public function dailyAt($time)
    {
        $segments = explode(':', $time);

        return $this->spliceIntoPosition(2, (int)$segments[0])
            ->spliceIntoPosition(1, count($segments) == 2 ? (int)$segments[1] : '0');
    }

    /**
     * 每天执行两次
     * Schedule the event to run twice daily.
     *
     * @param int $first
     * @param int $second
     * @return $this
     */
    public function twiceDaily($first = 1, $second = 13)
    {
        $hours = $first . ',' . $second;

        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, $hours);
    }

    /**
     * 工作日执行
     * Schedule the event to run only on weekdays.
     *
     * @return $this
     */
    public function weekdays()
    {
        return $this->spliceIntoPosition(5, '1-5');
    }

    /**
     * 周末执行
     * Schedule the event to run only on weekends.
     *
     * @return $this
     */
    public function weekends()
    {
        return $this->spliceIntoPosition(5, '0,6');
    }

    /**
     * 星期一执行
     * Schedule the event to run only on Mondays.
     *
     * @return $this
     */
    public function mondays()
    {
        return $this->days(1);
    }

    /**
     * 星期二执行
     * Schedule the event to run only on Tuesdays.
     *
     * @return $this
     */
    public function tuesdays()
    {
        return $this->days(2);
    }

    /**
     * 星期三执行
     * Schedule the event to run only on Wednesdays.
     *
     * @return $this
     */
    public function wednesdays()
    {
        return $this->days(3);
    }

    /**
     * 星期四执行
     * Schedule the event to run only on Thursdays.
     *
     * @return $this
     */
    public function thursdays()
    {
        return $this->days(4);
    }

    /**
     * 星期五执行
     * Schedule the event to run only on Fridays.
     *
     * @return $this
     */
    public function fridays()
    {
        return $this->days(5);
    }

    /**
     * 星期六执行
     * Schedule the event to run only on Saturdays.
     *
     * @return $this
     */
    public function saturdays()
    {
        return $this->days(6);
    }

    /**
     * 星期日执行
     * Schedule the event to run only on Sundays.
     *
     * @return $this
     */
    public function sundays()
    {
        return $this->days(0);
    }

    /**
     * 按周执行
     * Schedule the event to run weekly.
     *
     * @return $this
     */
    public function weekly()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(5, 0);
    }

    /**
     * 指定每周的时间执行
     * Schedule the event to run weekly on a given day and time.
     *
     * @param int $day
     * @param string $time
     * @return $this
     */
    public function weeklyOn($day, $time = '0:0')
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(5, $day);
    }

    /**
     * 按月执行
     * Schedule the event to run monthly.
     *
     * @return $this
     */
    public function monthly()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 1);
    }

    /**
     * 指定每月的执行时间
     * Schedule the event to run monthly on a given day and time.
     *
     * @param int $day
     * @param string $time
     * @return $this
     */
    public function monthlyOn($day = 1, $time = '0:0')
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, $day);
    }

    /**
     * 每月执行两次
     * Schedule the event to run twice monthly.
     *
     * @param int $first
     * @param int $second
     * @return $this
     */
    public function twiceMonthly($first = 1, $second = 16)
    {
        $days = $first . ',' . $second;

        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, $days);
    }

    /**
     * 每月最后一天几点执行
     *
     * @param string $time
     * @return $this
     */
    public function lastDayOfMonth($time = '0:0')
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, Carbon::now()->endOfMonth()->day);
    }

    /**
     * 按季度执行
     * Schedule the event to run quarterly.
     *
     * @return $this
     */
    public function quarterly()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 1)
            ->spliceIntoPosition(4, '1-12/3');
    }

    /**
     * 按年执行
     * Schedule the event to run yearly.
     *
     * @return $this
     */
    public function yearly()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 1)
            ->spliceIntoPosition(4, 1);
    }

    /**
     * 按年设置月日时间执行
     *
     * @param int $month
     * @param int|string $dayOfMonth
     * @param string $time
     * @return $this
     */
    public function yearlyOn($month = 1, $dayOfMonth = 1, $time = '0:0')
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, $dayOfMonth)
            ->spliceIntoPosition(4, $month);
    }

    /**
     * 按周设置天执行
     * Set the days of the week the command should run on.
     *
     * @param array|mixed $days
     * @return $this
     */
    public function days($days)
    {
        $days = is_array($days) ? $days : func_get_args();

        return $this->spliceIntoPosition(5, implode(',', $days));
    }

    /**
     * 设置时区
     * Set the timezone the date should be evaluated on.
     *
     * @param \DateTimeZone|string $timezone
     * @return $this
     */
    public function timezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Splice the given value into the given position of the expression.
     *
     * @param int $position
     * @param string $value
     * @return $this
     */
    protected function spliceIntoPosition($position, $value)
    {
        $segments = preg_split("/\s+/", $this->expression);

        $segments[$position - 1] = $value;

        return $this->cron(implode(' ', $segments));
    }
}
