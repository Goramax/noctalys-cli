<?php

namespace Noctalys\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Noctalys\Cli\Command\Step\Init\Name;
use Noctalys\Cli\Command\Step\Init\Path;
use Noctalys\Cli\Command\Step\Init\Template;
use Noctalys\Cli\Command\Step\Init\RepositoryClone;
use Noctalys\Cli\Command\Step\Init\BaseProject;
use Noctalys\Cli\Command\Step\Init\Type;
use Noctalys\Cli\Command\Step\Init\Setup;

class InitCommand extends Command
{
    protected static $defaultName = 'init';

    /**
     * Configure the command options
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('init')
            ->setDescription('Initialize a new Noctalys project')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Project name')
            ->addOption('description', 'd', InputOption::VALUE_OPTIONAL, 'Project description')
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Path to create the project in', getcwd())
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Project type (Backend, Frontend, Mixed)')
            ->addOption('template-engine', 'te', InputOption::VALUE_REQUIRED, 'Template engine (twig, blade, raw)')
            // ->addOption('css-framework', 'cf', InputOption::VALUE_REQUIRED, 'CSS framework (tailwind, none)') // TODO LATER
            ->addOption('base-project', null, InputOption::VALUE_REQUIRED , 'Base project to use for initialization if the project type is Frontend or Mixed (Complete or Minimal)')
            ->addOption('frameworkVersion', 'fv', InputOption::VALUE_OPTIONAL, 'Noctalys framework version to use')
            ->setHelp('This command allows you to create a new Noctalys project by guiding you through a series of steps.');
    }

    /**
     * Execute the command logic
     *
     * @param InputInterface $input Command input
     * @param OutputInterface $output Command output
     * @return int Command exit code
     */
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
            new Type(),
            new Template(),
            new BaseProject(),
            new RepositoryClone(),
            new Setup(),
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
