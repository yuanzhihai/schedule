<?php


namespace schedule;

use schedule\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Run extends Command
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
