<?php

namespace Goramax\NoctalysCli\Command\Step\Init;
use Goramax\NoctalysCli\Command\Step\StepInterface;

class Path implements StepInterface
{
    public function run(array &$context): bool
    {
        $input = $context['input'];
        $output = $context['output'];
        
        // Get base path from input option or use current directory
        $basePath = $input->getOption('path') ?: getcwd();
        
        // Use kebab-case version of the name for the directory
        $dirName = $context['name_kebab'];
        
        // Combine to get target path
        $targetPath = rtrim($basePath, '/') . '/' . $dirName;
        
        // Store in context
        $context['target'] = $targetPath;
        $output->writeln("Project will be created in: <info>$targetPath</info>");
        
        return true;
    }
}