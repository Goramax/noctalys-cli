<?php

namespace Goramax\NoctalysCli\Command\Step\Init;
use Goramax\NoctalysCli\Command\Step\StepInterface;
use Symfony\Component\Console\Question\Question;
use Goramax\NoctalysCli\Utils\StringTransformer;

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
                    'Enter the name of your project (default: "noctalys-project", use quotes for names with spaces): ',
                    'noctalys-project'
                );
                $name = $helper->ask($input, $output, $question);

                // === Validate project name ===
                if ($this->isQuoted($name)) {
                    // If name is quoted, allow spaces and capital letters
                    $isValid = true;
                } else if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
                    $output->writeln('<error>Invalid project name. Use quotes for names with spaces, or stick to alphanumeric characters, underscores, and hyphens.</error>');
                    $isValid = false;
                } else {
                    $isValid = true;
                }
            } while (!$isValid);
            
            // Ask for optional project description
            $descQuestion = new Question('Enter a project description (press Return key to skip): ', '');
            $description = $helper->ask($input, $output, $descQuestion);
            
        } else {
            // Validate provided name option
            if (!$this->isQuoted($name) && !preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
                $output->writeln('<error>Invalid project name provided. Use quotes for names with spaces, or stick to alphanumeric characters, underscores, and hyphens.</error>');
                return false;
            }
            $output->writeln('Welcome to the Noctalys CLI project initializer!');
            
            // Ask for optional project description
            $descQuestion = new Question('Enter a project description (press Return key to skip): ', '');
            $description = $helper->ask($input, $output, $descQuestion);
        }

        // Store the original name (with quotes removed if present)
        $originalName = trim($name, '"\'');
        $context['name'] = $originalName;
        
        // Add transformed versions to the context
        $context['name_kebab'] = StringTransformer::toKebabCase($name);
        $context['name_camel'] = StringTransformer::toCamelCase($name);
        $context['description'] = $description;
        
        $output->writeln("Project name set to: <info>$originalName</info>");
        $output->writeln("Directory name will be: <info>" . $context['name_kebab'] . "</info>");
        $output->writeln("Namespace will be: <info>" . $context['name_camel'] . "</info>");
        
        if (!empty($description)) {
            $output->writeln("Project description: <info>$description</info>");
        }
        
        return true;
    }
    
    /**
     * Check if a string is enclosed in quotes
     */
    private function isQuoted(string $string): bool
    {
        $string = trim($string);
        return (
            (substr($string, 0, 1) === '"' && substr($string, -1) === '"') ||
            (substr($string, 0, 1) === "'" && substr($string, -1) === "'")
        );
    }
}