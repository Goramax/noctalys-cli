<?php

namespace Noctalys\Cli\Command\Step\Init;
use Noctalys\Cli\Command\Step\StepInterface;
use Noctalys\Cli\Command\Step\Init\Setup\NoctalysFile;
use Noctalys\Cli\Command\Step\Init\Setup\TemplateFilesSetup;
use Noctalys\Cli\Command\Step\Init\Setup\UpdateFiles;
use Noctalys\Cli\Command\Step\Init\Setup\CleanFiles;

class Setup implements StepInterface
{
    /**
     * Run all substeps from the setup directory
     */
    public function run(array &$context): bool
    {

        // Define the steps to execute
        $steps = [
            new NoctalysFile(),
            new TemplateFilesSetup(),
            new UpdateFiles(),
            new CleanFiles(),
        ];

        // Execute each step
        foreach ($steps as $step) {
            $result = $step->run($context);
            
            // If a step returns false, stop execution
            if ($result === false) {
                return false;
            }
        }
        return true;
    }
}