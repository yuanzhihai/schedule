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
        $this->command('test')->everyMinute();
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
        'hello' => 'app\command\Hello',
    ]
];
```

第三步，命令行下运行

```
php think schedule:run
```

