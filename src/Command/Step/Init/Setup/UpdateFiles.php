<?php
// update config file with new values and update composer.json with project name, and description and update namespaces
namespace Goramax\NoctalysCli\Command\Step\Init\Setup;
use Goramax\NoctalysCli\Command\Step\StepInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class UpdateFiles implements StepInterface
{
    public function run(array &$context): bool
    {
        $output = $context['output'];
        $input = $context['input'];
        $target = $context['target'];

        // Update .noctalys file
        $noctalysFilePath = $target . '/.noctalys';
        if (file_exists($noctalysFilePath)) {
            // Logic to update the .noctalys file with new values
            file_put_contents($noctalysFilePath, "Updated Noctalys project configuration");
            $output->writeln("Updated .noctalys file at <info>$noctalysFilePath</info>");
        } else {
            $output->writeln("<error>.noctalys file does not exist at $noctalysFilePath</error>");
            return false;
        }

        // Update composer.json
        $composerFilePath = $target . '/composer.json';
        if (file_exists($composerFilePath)) {
            // Logic to update composer.json with project name and description
            $composerData = json_decode(file_get_contents($composerFilePath), true);
            $composerData['name'] = 'new/project-name'; // Example update
            $composerData['description'] = 'Updated project description'; // Example update
            file_put_contents($composerFilePath, json_encode($composerData, JSON_PRETTY_PRINT));
            $output->writeln("Updated composer.json at <info>$composerFilePath</info>");
        } else {
            $output->writeln("<error>composer.json does not exist at $composerFilePath</error>");
            return false;
        }

        return true;
    }
}