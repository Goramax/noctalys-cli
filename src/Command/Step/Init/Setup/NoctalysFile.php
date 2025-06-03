<?php
// setup of .noctalys file

namespace Goramax\NoctalysCli\Command\Step\Init\Setup;

use Goramax\NoctalysCli\Command\Step\StepInterface;
class NoctalysFile implements StepInterface
{
    public function run(array &$context): bool
    {
        $output = $context['output'];
        $target = $context['target'];
        
        // Get values from context
        $namespace = $context['name_camel'] ?? 'NoctalysDemoApp';
        $projectType = $context['type'] ?? 'Mixed';
        
        // Create configuration array
        $configData = [
            'namespace' => $namespace,
            'projectType' => $projectType,
            'creationDate' => date('Y-m-d H:i:s'),
            'frameworkVersion' => '1.0.0' // Hardcoded for now, could be from context or a constant
        ];
        
        // Convert to JSON
        $jsonContent = json_encode($configData, JSON_PRETTY_PRINT);

        $noctalysFilePath = $target . '/.noctalys';
        if (!file_exists($noctalysFilePath)) {
            file_put_contents($noctalysFilePath, $jsonContent);
            $output->writeln("Created .noctalys configuration file at <info>$noctalysFilePath</info>");
        } else {
            $output->writeln("<comment>Updating with new configuration...</comment>");
            file_put_contents($noctalysFilePath, $jsonContent);
            $output->writeln("<info>Configuration updated successfully</info>");
        }
        return true;
    }
}