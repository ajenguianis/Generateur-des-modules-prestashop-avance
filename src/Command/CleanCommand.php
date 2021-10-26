<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class CleanCommand extends Command
{
    use LockableTrait;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('modules:storage:clean')
            // the short description shown while running "php bin/console list"
            ->setDescription('Clean modules storage.')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to clean modules storage ...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '-1');
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }
        $output->writeln(['START', '============', '']);
        dump('test');
        $base_dir = $this->container->getParameter('kernel.project_dir');
        $this->dir = $base_dir . DIRECTORY_SEPARATOR . 'downloads';
        $filesystem=new Filesystem();
        $filesystem->remove($this->dir);
        if (!is_dir($this->dir) && !@mkdir($this->dir, 0777, true) && !is_dir($this->dir)) {
            throw new \RuntimeException(sprintf('Cannot create directory "%s"', $this->dir));
        }

        $output->writeln(['END', '============', '']);
        $this->release();
        return Command::SUCCESS;
    }
}
