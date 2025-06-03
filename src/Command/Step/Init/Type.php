<?php

namespace Goramax\NoctalysCli\Command\Step\Init;
use Goramax\NoctalysCli\Command\Step\StepInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class Type implements StepInterface
{
    public function run(array &$context): bool
    {
        $helper = $context['helper'];
        $input = $context['input'];
        $output = $context['output'];

        // Define valid project types
        $validTypes = ['Frontend', 'Backend', 'Mixed'];

        // Check if type is provided as option
        $type = $input->getOption('type');

        if (!$type) {
            // Ask user to choose project type
            $question = new ChoiceQuestion(
                'Choose a starter template',
                $validTypes,
                0
            );
            $type = $helper->ask($input, $output, $question);
        } else {
            // Validate provided type option
            if (!in_array($type, $validTypes)) {
                $output->writeln("<error>Invalid starter template '$type'. Valid options are: " . implode(', ', $validTypes) . "</error>");
                return false;
            }
        }

        $context['type'] = $type;
        $output->writeln("Starter template selected: <info>$type</info>");

        return true;
    }
}