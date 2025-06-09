<?php

namespace Goramax\NoctalysCli\Command\Step;

interface StepInterface {
    /**
     * Run the step
     * 
     * @param array &$context Context data shared between steps
     * @return bool True if successful, false otherwise
     */
    public function run(array &$context): bool;
}
