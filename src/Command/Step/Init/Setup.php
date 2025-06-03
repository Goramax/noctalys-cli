<?php

namespace Goramax\NoctalysCli\Command\Step\Init;
use Goramax\NoctalysCli\Command\Step\StepInterface;
use Goramax\NoctalysCli\Command\Step\Init\Setup\NoctalysFile;
use Goramax\NoctalysCli\Command\Step\Init\Setup\TemplateFilesSetup;
use Goramax\NoctalysCli\Command\Step\Init\Setup\UpdateFiles;
use Goramax\NoctalysCli\Command\Step\Init\Setup\CleanFiles;

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