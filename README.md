# schedule
thinkphp 任务调度

代码实现主要参考 laravel 相关用法请参考 laravel

具体用法：

第一步
运行指令

```
php think make:command Schedule schedule:run
```
会生成一个app\console\Schedule命令行指令类，我们修改内容如下：
```
namespace app\command;

use schedule\console\Command;
use think\console\Input;
use think\console\Output;

class Schedule extends Command
{
    protected function configure()
    {
        $this->setName('schedule:run');
    }

    protected function execute(Input $input, Output $output)
    {
        //每天的上午十点和晚上八点执行这个命令
        $this->command('test')->twiceDaily(10, 20);
        parent::execute($input, $output);
    }
}
```

继续运行指令
```
php think make:command Test test
```

第二步，配置config/console.php文件

```
<?php
return [
    'commands' => [
        'schedule:run'=>\app\command\Schedule::class,
        'test' => 'app\command\Test',
    ]
];
```

第三步,您应该在crontab中添加以下命令：

```
* * * * * php /path/to/think schedule:run  >> /dev/null 2>&1 
```

时间表范例
此扩展支持Laravel Schedule的所有功能，环境和维护模式除外。
Scheduling Closures
```
$this->call(function()
{
    // Do some task...

})->hourly();
```
Running command of your application
```
$this->command('migrate')->cron('* * * * *');
```
Frequent Jobs
```
$this->command('foo')->everyFiveMinutes();

$this->command('foo')->everyTenMinutes();

$this->command('foo')->everyThirtyMinutes();
```
Daily Jobs
```
$this->command('foo')->daily();
```
Daily Jobs At A Specific Time (24 Hour Time)

```
$this->command('foo')->dailyAt('15:00');
```
Twice Daily Jobs
```
$this->command('foo')->twiceDaily();
```
Job That Runs Every Weekday
```
$this->command('foo')->weekdays();
```
Weekly Jobs
```
$this->command('foo')->weekly();

// Schedule weekly job for specific day (0-6) and time...
$this->command('foo')->weeklyOn(1, '8:00');
```

Monthly Jobs
```
$this->command('foo')->monthly();
```
Job That Runs On Specific Days
```
$this->command('foo')->mondays();
$this->command('foo')->tuesdays();
$this->command('foo')->wednesdays();
$this->command('foo')->thursdays();
$this->command('foo')->fridays();
$this->command('foo')->saturdays();
$this->command('foo')->sundays();
```

Only Allow Job To Run When Callback Is True
```
$this->command('foo')->monthly()->when(function()
{
    return true;
});
```

