<?php

namespace Goramax\NoctalysCli\Command\Step\Init;
use Goramax\NoctalysCli\Command\Step\StepInterface;
use Symfony\Component\Console\Question\Question;

class Path implements StepInterface
{
    public function run(array &$context): bool
    {
        $input = $context['input'];
        $output = $context['output'];
        $name = $context['name'];

        // Check if path is provided as option, otherwise use current directory
        $path = $input->getOption('path');
        if ($path) {
            $target = rtrim($path, '/') . '/' . $name;
        } else {
            $target = getcwd() . '/' . $name;
        }

        // Check if the target directory already exists
        while (is_dir($target)) {
            $output->writeln("<error>Directory '$target' already exists. Please choose a different name or path.</error>");
            $helper = $context['helper'];
            $question = new Question('Enter a new path: ', $path);
            $newPath = $helper->ask($input, $output, $question);
            if ($newPath) {
                $path = $newPath;
                $target = rtrim($path, '/') . '/' . $name;
            } else {
                $target = getcwd() . '/' . $name;
            }
        }

        $context['target'] = $target;
        $output->writeln("Target directory: <info>$target</info>");
        
        return true;
    }
}