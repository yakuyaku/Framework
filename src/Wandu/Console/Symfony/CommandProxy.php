<?php
namespace Wandu\Console\Symfony;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wandu\Console\Controller;

class CommandProxy extends SymfonyCommand
{
    public function __construct($name, Controller $command)
    {
        parent::__construct($name);
        $this->command = $command;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->command->withIO($input, $output)->execute();
    }
}