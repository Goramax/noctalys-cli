<?php

namespace Goramax\NoctalysCli\Command\Step\Init;
use Goramax\NoctalysCli\Command\Step\StepInterface;
use Symfony\Component\Console\Question\Question;

class Name implements StepInterface
{
    public function run(array &$context): bool
    {
        $helper = $context['helper'];
        $input = $context['input'];
        $output = $context['output'];

        // Check if name is provided as option
        $name = $input->getOption('name');
        
        if (!$name) {
            // === Prompt for project name ===
            $output->writeln('Welcome to the Noctalys CLI project initializer!');
            
            do {
                $question = new Question(
                    'Enter the name of your project (default: "noctalys-project"): ',
                    'noctalys-project'
                );
                $name = $helper->ask($input, $output, $question);

                // === Validate project name ===
                if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
                    $output->writeln('<error>Invalid project name. Only alphanumeric characters, underscores, and hyphens are allowed.</error>');
                    $isValid = false;
                } else {
                    $isValid = true;
                }
            } while (!$isValid);
        } else {
            // Validate provided name option
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
                $output->writeln('<error>Invalid project name provided. Only alphanumeric characters, underscores, and hyphens are allowed.</error>');
                return false;
            }
            $output->writeln('Welcome to the Noctalys CLI project initializer!');
        }

        $context['name'] = $name;
        $output->writeln("Project name set to: <info>$name</info>");
        
        return true;
    }
}