<?php

declare(strict_types=1);

namespace EvoGroup\Module\Moduleclass\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SampleCommand extends ContainerAwareCommand
{
    use LockableTrait;

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('command_call')
            // the short description shown while running "php bin/console list"
            ->setDescription('command description.')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows yo to ...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }
        $output->writeln(['START', '============', '']);


        $output->writeln(['END', '============', '']);
        $this->release();
    }
}