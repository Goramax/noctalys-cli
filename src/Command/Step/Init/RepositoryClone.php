<?php

namespace Goramax\NoctalysCli\Command\Step\Init;

use Goramax\NoctalysCli\Command\Step\StepInterface;

class RepositoryClone implements StepInterface
{
    public function run(array &$context): bool
    {
        $output = $context['output'];
        $template = $context['template'];
        $target = $context['target'];

        $repo = "https://github.com/Goramax/noctalys-demo-app.git";

        $output->writeln("Cloning from $repo to $target");
        exec("git clone $repo $target", $outputLines, $returnCode);

        if ($returnCode !== 0) {
            $output->writeln("<error>Failed to clone repository</error>");
            return false;
        }
        
        // Remove the .git directory to avoid confusion with the original repository
        $gitDir = $target . '/.git';
        if (is_dir($gitDir)) {
            exec("rm -rf " . escapeshellarg($gitDir), $rmOutput, $rmCode);
            if ($rmCode !== 0) {
                $output->writeln("<error>Failed to remove .git directory</error>");
                return false;
            }
        }

        $output->writeln("Cloning completed successfully to $target");
        return true;
    }
}
