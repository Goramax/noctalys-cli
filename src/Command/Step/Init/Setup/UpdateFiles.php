<?php

namespace Goramax\NoctalysCli\Command\Step\Init\Setup;

use Goramax\NoctalysCli\Command\Step\StepInterface;
use Goramax\NoctalysCli\Utils\StringTransformer;
use Goramax\NoctalysCli\Utils\ConfigManager;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class UpdateFiles implements StepInterface
{
    private $output;
    private $input;
    private $target;

    public function run(array &$context): bool
    {
        $this->output = $context['output'];
        $this->input  = $context['input'];
        $this->target = $context['target'];

        $projectName      = $context['name_kebab'] ?? basename($this->target);
        $projectNamespace = $context['name_camel'] ?? StringTransformer::toCamelCase($context['name']);
        $projectRealName = $context['name'] ?? $projectName;

        if (!$this->updateComposerFile(
            $this->target . '/composer.json',
            $projectName,
            $projectNamespace,
            $context
        )) {
            return false;
        }

        $configFile = $this->target . '/config.json';
        $templateEngine = strtolower($context['template-engine'] ?? '');
        $type = strtolower($context['type'] ?? '');

        if (!$this->updateConfigFile(
            $configFile,
            $projectRealName,
            $templateEngine,
            $type
        )) {
            return false;
        }

        $this->output->writeln("Updated config file at <info>$configFile</info>");

        // Update file tree based on project type
        $this->updateFileTree($type);
        $this->output->writeln("<info>File tree updated based on project type: $type</info>");

        return true;
    }

    private function updateComposerFile(string $filePath, string $projectName, string $namespace, array $context): bool
    {
        if (!file_exists($filePath)) {
            $this->output->writeln("<error>composer.json does not exist at $filePath</error>");
            return false;
        }

        $composerData = json_decode(file_get_contents($filePath), true);
        $composerData['name']        = 'vendor/' . $projectName;
        $composerData['description'] = $context['description'] ?? $composerData['description'] ?? '';
        $composerData['autoload']['psr-4'] = [
            $namespace . '\\' => 'src/',
        ];

        // Set the framework version from context if available
        if (!empty($context['frameworkVersion'])) {
            if (!isset($composerData['require'])) {
                $composerData['require'] = [];
            }
            $composerData['require']['noctalys/framework'] = $context['frameworkVersion'];
        }

        file_put_contents(
            $filePath,
            json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );

        $this->output->writeln("Updated composer.json at <info>$filePath</info>");
        return true;
    }

    private function updateConfigFile(
        string $filePath,
        string $projectName,
        string $templateEngine,
        string $type
    ): bool {
        if (!file_exists($filePath)) {
            $this->output->writeln("<error>Config file does not exist at $filePath</error>");
            return false;
        }

        // Load config using ConfigManager
        $config = ConfigManager::load($filePath);
        if ($config === null) {
            $this->output->writeln("<error>Invalid JSON in config file</error>");
            return false;
        }

        // Update app.name if it exists
        if (ConfigManager::has($config, 'app.name')) {
            ConfigManager::set($config, 'app.name', $projectName);
        }

        // Update template engine if frontend project and template_engine.engine exists
        if ($type === 'frontend' && ConfigManager::has($config, 'template_engine.engine')) {
            // Map 'none' to 'no'
            $engineValue = ($templateEngine === 'none') ? 'no' : $templateEngine;
            ConfigManager::set($config, 'template_engine.engine', $engineValue);
        }

        // Save config
        if (!ConfigManager::save($filePath, $config)) {
            $this->output->writeln("<error>Failed to write config file at $filePath</error>");
            return false;
        }

        $this->output->writeln("Updated config file at <info>$filePath</info>");
        return true;
    }

    private function updateFileTree($type){
        // if mixed, do nothing
        if ($type === 'mixed') {
            $this->output->writeln("<info>Mixed project type, no file tree updates needed.</info>");
            return;
        }

        $srcDir = $this->target . '/src';
        $frontendDir = $srcDir . '/Frontend';
        $backendDir = $srcDir . '/Backend';

        if ($type === 'frontend') {
            if (is_dir($frontendDir)) {
                $this->output->writeln("<info>Moving Frontend files to src/</info>");
                $this->recursiveCopy($frontendDir, $srcDir);
                $this->recursiveRemoveDir($frontendDir);
            } else {
                $this->output->writeln("<comment>No Frontend directory found to move.</comment>");
            }
        } elseif ($type === 'backend') {
            if (is_dir($backendDir)) {
                $this->output->writeln("<info>Moving Backend files to src/</info>");
                $this->recursiveCopy($backendDir, $srcDir);
                $this->recursiveRemoveDir($backendDir);
            } else {
                $this->output->writeln("<comment>No Backend directory found to move.</comment>");
            }
        }
    }
    private function recursiveCopy(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = "$source/$file";
            $destinationPath = "$destination/$file";

            if (is_dir($sourcePath)) {
                $this->recursiveCopy($sourcePath, $destinationPath);
            } else {
                copy($sourcePath, $destinationPath);
            }
        }

        closedir($dir);
    }

    private function recursiveRemoveDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $filePath = "$dir/$file";
            if (is_dir($filePath)) {
                $this->recursiveRemoveDir($filePath);
            } else {
                unlink($filePath);
            }
        }
        rmdir($dir);
    }
}