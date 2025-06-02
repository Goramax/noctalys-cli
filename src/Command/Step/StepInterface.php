<?php

namespace Goramax\NoctalysCli\Command\Step;

interface StepInterface {
    public function run(array &$context): bool;
}
