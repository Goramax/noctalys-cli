<?php
// setup of frontend template engine + css framework

namespace Goramax\NoctalysCli\Command\Step\Init\Setup;
use Goramax\NoctalysCli\Command\Step\StepInterface;

class TemplateFilesSetup implements StepInterface
{
    public function run(array &$context): bool
    {
        // TODO : New repo for template files
        // TODO : Then clone the template files repository
        // TODO : Construct from the template files and the json
        return true;
    }
}