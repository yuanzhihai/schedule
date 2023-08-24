# schedule
thinkphp 任务调度

代码实现主要参考 laravel 相关用法请参考 laravel

具体用法：

第一步
运行指令

```
php think schedule:init
```
会在项目目录app下生成一个 ConsoleScheduling 类
```
namespace app;

use schedule\scheduling\ScheduleConsole;
use schedule\scheduling\Schedule;

class ConsoleScheduling extends ScheduleConsole
{
    /**
     * 定义任务计划
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command("test")->onOneServer()->everyMinute();
        $schedule->exec('echo 555')->everyMinute();
        $schedule->call(function () {
            file_put_contents(runtime_path()."console-scheduling.log", time() . PHP_EOL, FILE_APPEND);
        })->everyMinute();
    }
}
```

第二步，在 event.php 里为 AppInit 事件添加监听类。

```
return [
...
    'listen' => [
        ...
        'AppInit'                               => [
            \app\ConsoleScheduling::class
        ],
        ...
    ],
...
];
```

第三步,运行以下命令

```
php /path/to/think schedule:run
```

时间表范例
此扩展支持Laravel Schedule的所有功能

### 定义调度
我们计划每天午夜执行一个闭包，这个闭包会执行一次数据库语句去清空一张表
```php
protected function schedule(Schedule $schedule): void
{
    $schedule->call(function () {
       Db::table('recent_users')->delete();
    })->daily();
}
```
除了调用闭包这种方式来调度外，你还可以调用 可调用对象。 可调用对象是简单的 PHP 类，包含一个<code> __invoke</code> 方法：
```php
$schedule->call(new DeleteRecentUsers)->daily();
```

如果你想查看任务计划的概述及其下次计划运行时间，你可以使用 <code>schedule:list</code> think 命令

```php
php think schedule:list
```
### thinkphp 命令调度

调度方式不仅有调用闭包，还有调用 Thinkphp commands 和操作系统命令。例如，你可以给 command 方法传递命令名称或类来调度一个 <code>Thinkphp</code> 命令：

当使用命令类名调度 <code>Thinkphp</code> 命令时，你可以通过一个数组传递附加的命令行参数，且这些参数需要在命令触发时提供：

```php

use app\command\SendEmails;

$schedule->command('emails:send Taylor --force')->daily();

$schedule->command(SendEmails::class, ['Taylor', '--force'])->daily();

```
### 队列任务调度
<code>job</code> 方法可以用来调度 queued job。此方法提供了一种快捷方式来调度任务，而无需使用 <code>call</code> 方法创建闭包来调度任务：\
```php
use app\jhobs\Heartbeat;

$schedule->job(new Heartbeat)->everyFiveMinutes();
```
<code>job</code> 方法提供了可选的第二，三参数，分别指定任务将被放置的队列名称及连接：

```php
use app\jobs\Heartbeat;
// 分发任务到「heartbeats」队列及「redis」连接...
$schedule->job(new Heartbeat, 'heartbeats', 'redis')->everyFiveMinutes();
```
### Shell 命令调度

<code>exec</code> 方法可发送命令到操作系统：
```php
$schedule->exec('node /home/forge/script.js')->daily();
```

### 调度频率选项
我们已经看到了几个如何设置任务在指定时间间隔运行的例子。不仅如此，你还有更多的任务调度频率可选:

| 方法                                           | 描述                        |
|----------------------------------------------|---------------------------|
| <code>->cron('* * * * *');</code>            | 自定义 Cron 计划执行任务           |
| <code>->everySecond();</code>                | 每秒钟执行一次任务                 |
| <code>->everyTwoSeconds();</code>            | 每 2 秒钟执行一次任务              |
| <code>->everyFiveSeconds();</code>           | 每 5 秒钟执行一次任务              |
| <code>->everyTenSeconds();</code>            | 每 10 秒钟执行一次任务             |
| <code>->everyFifteenSeconds();</code>        | 每 15 秒钟执行一次任务             |
| <code>->everyTwentySeconds();</code>         | 每 20 秒钟执行一次任务             |
| <code>->everyThirtySeconds();</code>         | 每 30 秒钟执行一次任务             |
| <code>->everyMinute();</code>                | 每分钟执行一次任务                 |
| <code>->everyTwoMinutes();</code>            | 每两分钟执行一次任务                |
| <code>->everyThreeMinutes();</code>          | 每三分钟执行一次任务                |
| <code>->everyFourMinutes();</code>           | 每四分钟执行一次任务                |
| <code>->everyFiveMinutes();</code>           | 每五分钟执行一次任务                |
| <code>->everyTenMinutes();</code>            | 每十分钟执行一次任务                |
| <code>->everyFifteenMinutes();</code>        | 每十五分钟执行一次任务               |
| <code>->everyThirtyMinutes();</code>         | 每三十分钟执行一次任务               |
| <code>->hourly();</code>                     | 每小时执行一次任务                 |
| <code>->hourlyAt(17);</code>                 | 每小时第十七分钟时执行一次任务           |
| <code>->everyTwoHours();</code>              | 每两小时执行一次任务                |
| <code>->everyThreeHours();</code>            | 每三小时执行一次任务                |
| <code>->everyFourHours();</code>             | 每四小时执行一次任务                |
| <code>->everySixHours();</code>              | 每六小时执行一次任务                |
| <code>->daily();</code>                      | 每天 00:00 执行一次任务           |
| <code>->dailyAt('13:00');</code>             | 每天 13:00 执行一次任务           |
| <code>->twiceDaily(1, 13);</code>            | 每天 01:00 和 13:00 各执行一次任务  |
| <code>->twiceDailyAt(1, 13, 15);</code>      | 每天 1:15 和 13:15 各执行一次任务   |
| <code>->weekly();</code>                     | 每周日 00:00 执行一次任务          |
| <code>->weeklyOn(1, '8:00');</code>          | 每周一 08:00 执行一次任务          |
| <code>->monthly();</code>                    | 每月第一天 00:00 执行一次任务        |
| <code>->monthlyOn(4, '15:00');</code>        | 每月第四天 15:00 执行一次任务        |
| <code>->twiceMonthly(1, 16, '13:00');</code> | 每月第一天和第十六天的 13:00 各执行一次任务 |
| <code>->lastDayOfMonth('15:00');</code>      | 每月最后一天 15:00 执行一次任务       |
| <code>->quarterly();</code>                  | 每季度第一天 00:00 执行一次任务       |
| <code>->quarterlyOn(4, '14:00');</code>      | 每季度第四天 14:00 运行一次任务       |
| <code>->yearly();</code>                     | 每年第一天 00:00 执行一次任务        |
| <code>->yearlyOn(6, 1, '17:00');</code>      | 每年六月第一天 17:00 执行一次任务      |
| <code>->timezone('America/New_York');</code> | 为任务设置时区                   |

这些方法与额外的约束条件相结合后，可用于创建在一周的特定时间运行甚至更精细的计划任务。例如，在每周一执行命令：
```php
// 在每周一 13:00 执行...
$schedule->call(function () {
    // ...
})->weekly()->mondays()->at('13:00');

// 在每个工作日 8:00 到 17:00 之间的每小时周期执行...
$schedule->command('foo')
          ->weekdays()
          ->hourly()
          ->timezone('America/Chicago')
          ->between('8:00', '17:00');
```

下方列出了额外的约束条件：

| 方法                                                  | 描述            |
|-----------------------------------------------------|---------------|
| <code>->weekdays();</code>                          | 限制任务在工作日执行    |
| <code>->weekends();</code>                          | 限制任务在周末执行     |
| <code>->sundays();</code>                           | 限制任务在周日执行     |
| <code>->mondays();</code>                           | 限制任务在周一执行     |
| <code>->tuesdays();</code>                          | 限制任务在周二执行     |
| <code>->wednesdays();</code>                        | 限制任务在周三执行     |
| <code>->thursdays();</code>                         | 限制任务在周四执行     |
| <code>->fridays();</code>                           | 限制任务在周五执行     |
| <code>->saturdays();</code>                         | 限制任务在周六执行     |
| <code>->days(array\|mixed);</code>                  | 限制任务在每周的指定日期执行|
| <code>->between($startTime, $endTime);</code>       |  限制任务在 $startTime 和 $endTime 区间执行             |
| <code>->unlessBetween($startTime, $endTime);</code> |   限制任务不在 $startTime 和 $endTime 区间执行            |
| <code>->when(Closure);</code>                       |   限制任务在闭包返回为真时执行            |

#### 周几（Day）限制
<code>days</code> 方法可以用于限制任务在每周的指定日期执行。举个例子，你可以在让一个命令每周日和每周三每小时执行一次：
```php
$schedule->command('email:send')
                ->hourly()
                ->days([0, 3]);
```
不仅如此，你还可以使用 schedule\scheduling\Schedule 类中的常量来设置任务在指定日期运行：

```php
use schedule\scheduling\Schedule;

$schedule->command('email:send')
                ->hourly()
                ->days([Schedule::SUNDAY, Schedule::WEDNESDAY]);
```
#### 时间范围限制
<code>between</code> 方法可用于限制任务在一天中的某个时间段执行：
```php
$schedule->command('email:send')
                    ->hourly()
                    ->between('7:00', '22:00');
```
同样， <code>unlessBetween</code> 方法也可用于限制任务不在一天中的某个时间段执行：
```php
$schedule->command('email:send')
                    ->hourly()
                    ->unlessBetween('23:00', '4:00');
```
#### 真值检测限制
<code>when</code> 方法可根据闭包返回结果来执行任务。换言之，若给定的闭包返回 <code>true</code>，若无其他限制条件阻止，任务就会一直执行：
```php
$schedule->command('email:send')->daily()->when(function () {
    return true;
});
```
<code>skip</code> 可看作是 <code>when</code> 的逆方法。若 <code>skip</code> 方法返回 <code>true</code>，任务将不会执行：
```php
$schedule->command('email:send')->daily()->skip(function () {
    return true;
});
```
当链式调用 <code>when</code> 方法时，仅当所有 <code>when</code> 都返回 <code>true</code> 时，任务才会执行。
#### 时区
<code>timezone</code> 方法可指定在某一时区的时间执行计划任务：
```php
$schedule->command('report:generate')
         ->timezone('America/New_York')
         ->at('2:00')
```
若想给所有计划任务分配相同的时区，那么需要在 app/ConsoleScheduling.php 类中定义 scheduleTimezone 方法。该方法会返回一个默认时区，最终分配给所有计划任务：
```php
use DateTimeZone;

/**
 * 获取计划事件默认使用的时区
 */
protected function scheduleTimezone(): DateTimeZone|string|null
{
    return 'America/Chicago';
}
```
<pre>
<b>注意</b>

请记住，有些时区会使用夏令时。当夏令时发生调整时，你的任务可能会执行两次，甚至根本不会执行。因此，我们建议尽可能避免使用时区来安排计划任务。
</pre>
#### 避免任务重复
默认情况下，即使之前的任务实例还在执行，调度内的任务也会执行。为避免这种情况的发生，你可以使用 <code>withoutOverlapping</code> 方法：

```php
$schedule->command('email:send')->withoutOverlapping();
```
在此例中，若 <code>email:send</code> Thinkphp 命令 还未运行，那它将会每分钟执行一次。如果你的任务执行时间非常不确定，导致你无法准确预测任务的执行时间，那 <code>withoutOverlapping</code> 方法会特别有用。
如有需要，你可以在 <code>withoutOverlapping</code> 锁过期之前，指定它的过期分钟数。默认情况下，这个锁会在 24 小时后过期

```php
$schedule->command('email:send')->withoutOverlapping(10);
```
上面这种场景中，<code>withoutOverlapping</code> 方法使用应用程序的 缓存 获取锁。如有必要，可以使用 <code>schedule:clear-cache</code> Thinkphp 命令清除这些缓存锁。这通常只有在任务由于意外的服务器问题而卡住时才需要。

### 任务只运行在一台服务器上
<pre>
注意
要使用此功能，你的应用程序必须使用 memcached 或 redis  缓存驱动程序作为应用程序的默认缓存驱动程序。
</pre>
```php
$schedule->command('report:generate')
                ->fridays()
                ->at('17:00')
                ->onOneServer();
```
#### 命名单服务器作业
有时，你可能需要使用不同的参数调度相同的作业，同时使其仍然在单个服务器上运行作业。为此，你可以使用 <code>name</code> 方法为每个作业定义一个唯一的名字：

```php
$schedule->job(new CheckUptime('https://think.com'))
            ->name('check_uptime:think.com')
            ->everyFiveMinutes()
            ->onOneServer();

$schedule->job(new CheckUptime('https://vapor.think.com'))
            ->name('check_uptime:vapor.think.com')
            ->everyFiveMinutes()
            ->onOneServer();
```
如果你使用闭包来定义单服务器作业，则必须为他们定义一个名字
```php
$schedule->call(fn () => User::resetApiRequestCount())
    ->name('reset-api-request-count')
    ->daily()
    ->onOneServer();
```
#### 后台任务
默认情况下，同时运行多个任务将根据它们在 schedule 方法中定义的顺序执行。如果你有一些长时间运行的任务，将会导致后续任务比预期时间更晚启动。 如果你想在后台运行任务，以便它们可以同时运行，则可以使用 <code>runInBackground</code> 方法:
```php
$schedule->command('analytics:report')
         ->daily()
         ->runInBackground();
```
<pre>
<b>注意</b>

<code>runInBackground</code> 方法只有在通过 <code>command</code> 和 <code>exec</code> 方法调度任务时才可以使用
</pre>

#### 维护模式
当应用处于 <code>维护模式</code> 时，任务将不会运行。因为我们不想调度任务干扰到服务器上可能还未完成的维护项目。不过，如果你想强制任务在维护模式下运行，你可以使用 <code>evenInMaintenanceMode</code> 方法

#### 运行调度程序
现在，我们已经学会了如何定义计划任务，接下来让我们讨论如何真正在服务器上运行它们。<code>schedule:run</code> Thinkphp 命令将评估你的所有计划任务，并根据服务器的当前时间决定它们是否运行。
```php
* * * * * cd /path-to-your-project && php think schedule:run >> /dev/null 2>&1
```
### 本地运行调度程序
通常，你不会直接将 cron 配置项添加到本地开发计算机。你反而可以使用 <code>schedule:work</code> Thinkphp 命令。该命令将在前台运行，并每分钟调用一次调度程序，直到你终止该命令为止
```php
php think schedule:work
```

#### 任务输出
schedule 调度器提供了一些简便方法来处理调度任务生成的输出。首先，你可以使用 sendOutputTo 方法将输出发送到文件中以便后续检查：
```php
$schedule->command('email:send')
         ->daily()
         ->sendOutputTo($filePath);
```
如果希望将输出追加到指定文件，可使用 <code>appendOutputTo</code> 方法：
```php
$schedule->command('emails:send')
         ->daily()
         ->appendOutputTo($filePath);
```
使用 <code>emailOutputTo</code> 方法，你可以将输出发送到指定邮箱。在发送邮件之前，你需要先安装 
```shell
composer require yzh52521/think-mail
```
```php
$schedule->command('report:generate')
         ->daily()
         ->sendOutputTo($filePath)
         ->emailOutputTo('taylor@example.com');
```

如果你只想在命令执行失败时将输出发送到邮箱，可使用 <code>emailOutputOnFailure</code> 方法：
```php
$schedule->command('report:generate')
         ->daily()
         ->emailOutputOnFailure('taylor@example.com');
```
<pre>
<b>注意</b>

emailOutputTo, emailOutputOnFailure, sendOutputTo 和 appendOutputTo 是 command 和 exec 独有的方法。
</pre>

#### 任务钩子
使用 <code>before</code> 和 <code>after</code> 方法，你可以决定在调度任务执行前或者执行后来运行代码：
```php
$schedule->command('email:send')
         ->daily()
         ->before(function () {
             // 任务即将执行。。。
         })
         ->after(function () {
             // 任务已经执行。。。
         });
```
使用 <code>onSuccess</code> 和 <code>onFailure</code> 方法，你可以决定在调度任务成功或者失败运行代码。失败表示 schedule 任务 或系统命令以非零退出码终止：
```php
$schedule->command('email:send')
         ->daily()
         ->onSuccess(function () {
             // 任务执行成功。。。
         })
         ->onFailure(function () {
             // 任务执行失败。。。
         });
```

#### Pinging 网址
使用 <code>pingBefore</code> 和 <code>thenPing</code> 方法，你可以在任务完成之前或完成之后来 ping 指定的 URL。当前方法在通知外部服务，如计划任务在将要执行或已完成时会很有用：
```php
$schedule->command('email:send')
         ->daily()
         ->pingBefore($url)
         ->thenPing($url);
```
只有当条件为 true 时，才可以使用 <code>pingBeforeIf</code> 和 <code>thenPingIf</code> 方法来 ping 指定 URL
```php
$schedule->command('email:send')
         ->daily()
         ->pingBeforeIf($condition, $url)
         ->thenPingIf($condition, $url);
```
当任务成功或失败时，可使用 <code>pingOnSuccess</code> 和 <code>pingOnFailure</code> 方法来 ping 给定 URL。失败表示 schedule 调度 或系统命令以非零退出码终止：
```php
$schedule->command('email:send')
         ->daily()
         ->pingOnSuccess($successUrl)
         ->pingOnFailure($failureUrl);
```
所有 ping 方法都依赖 Guzzle HTTP 库 使用 Composer 包管理器将 Guzzle 安装到项目中
```shell
composer require guzzlehttp/guzzle
```
#### 事件
如果需要，你可以监听调度程序调度的 事件。通常，事件侦听器映射将在你的应用程序的 event.php里配置 如下：
```php

'listen' => [
        'schedule\events\ScheduledTaskStarting' => [
            \app\listener\LogScheduledTaskStarting::class
        ],
        'schedule\events\ScheduledTaskFinished' => [
            \app\listener\LogScheduledTaskFinished::class,
        ],
        'schedule\events\ScheduledTaskSkipped' => [
            \app\listener\LogScheduledTaskSkipped::class,
        ],
        'schedule\events\ScheduledTaskFailed' => [
            \app\listener\LogScheduledTaskFailed::class,
        ],
];
```
