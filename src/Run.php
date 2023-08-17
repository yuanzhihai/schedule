<?php


namespace schedule;

use schedule\console\Command;
use think\console\Input;
use think\console\Output;

class Run extends Command
{
    protected function configure()
    {
        $this->setName( 'schedule:run' );
    }

    protected function execute(Input $input,Output $output)
    {
        parent::execute( $input,$output );
    }
}
