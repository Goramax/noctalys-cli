<?php

namespace Goramax\NoctalysCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Goramax\NoctalysCli\Command\Step\Init\Name;
use Goramax\NoctalysCli\Command\Step\Init\Path;
use Goramax\NoctalysCli\Command\Step\Init\Template;
use Goramax\NoctalysCli\Command\Step\Init\Repository;

class InitCommand extends Command
{
    protected static $defaultName = 'init';

    protected function configure(): void
    {
        $this
            ->setName('init')
            ->setDescription('Initialize a new Noctalys project')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Project name')
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Path to create the project in', getcwd())
            ->addOption('template-engine', 't', InputOption::VALUE_REQUIRED, 'Template engine (twig, blade, raw)')
            ->addOption('css-framework', 'c', InputOption::VALUE_REQUIRED, 'CSS framework (tailwind, none)')
            ->setHelp('This command allows you to create a new Noctalys project by guiding you through a series of steps.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        
        // Initialize context array that will be passed between steps
        $context = [
            'input' => $input,
            'output' => $output,
            'helper' => $helper
        ];

        // Define the steps to execute
        $steps = [
            new Name(),
            new Path(),
            new Template(),
            new Repository(),
            
        ];

        // Execute each step
        foreach ($steps as $step) {
            $result = $step->run($context);
            
            // If a step returns false, stop execution
            if ($result === false) {
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
