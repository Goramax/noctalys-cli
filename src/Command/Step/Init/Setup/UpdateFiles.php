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

    /**
     * Update project files with correct names, paths and configuration
     * 
     * @param array &$context Context data containing project information
     * @return bool True if file updates were successful
     */
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

        $this->updateFileTree($type);
        $this->output->writeln("<info>File tree updated based on project type: $type</info>");
        
        // Clean navigation component if project is minimal
        $baseProject = strtolower($context['base_project'] ?? '');
        if ($baseProject === 'minimal' && in_array($type, ['frontend', 'mixed'], true)) {
            $this->cleanNavComponent();
        }

        return true;
    }

    /**
     * Update composer.json file with project information
     * 
     * @param string $filePath Path to composer.json
     * @param string $projectName Project name in kebab-case
     * @param string $namespace Project namespace in CamelCase
     * @param array $context Full context data
     * @return bool True if successful
     */
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

        $config = ConfigManager::load($filePath);
        if ($config === null) {
            $this->output->writeln("<error>Invalid JSON in config file</error>");
            return false;
        }

        if (ConfigManager::has($config, 'app.name')) {
            ConfigManager::set($config, 'app.name', $projectName);
        }

        if ($type === 'frontend' && ConfigManager::has($config, 'template_engine.engine')) {
            $engineValue = ($templateEngine === 'none') ? 'no' : $templateEngine;
            ConfigManager::set($config, 'template_engine.engine', $engineValue);
        }

        if (!ConfigManager::save($filePath, $config)) {
            $this->output->writeln("<error>Failed to write config file at $filePath</error>");
            return false;
        }

        $this->output->writeln("Updated config file at <info>$filePath</info>");
        return true;
    }

    private function updateFileTree($type){
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

    /**
     * Clean the navigation component to only show the Home link for minimal projects
     * 
     * @return void
     */
    private function cleanNavComponent(): void
    {
        $navComponentPath = null;
        $searchDir = $this->target . '/src';

        if (is_dir($searchDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($searchDir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile() 
                    && preg_match('/^nav\.component\.[^\.\/]+$/i', $fileInfo->getFilename())
                ) {
                    $navComponentPath = $fileInfo->getPathname();
                    break;
                }
            }
        }

        if ($navComponentPath === null) {
            $this->output->writeln("<comment>Navigation component file not found. Skipping cleanup.</comment>");
            return;
        }

        $content = @file_get_contents($navComponentPath);
        if ($content === false) {
            $this->output->writeln("<error>Failed to read navigation component file: $navComponentPath</error>");
            return;
        }

        // Keep only the Home link in the navigation
        $cleanNav = '<nav>' . PHP_EOL
                  . '    <a href="/">Home</a>' . PHP_EOL
                  . '</nav>';

        $content = preg_replace(
            '/<nav\b.*?>.*?<a\s+href="[^"]*"\s*>Home<\/a>.*?<\/nav>/si',
            $cleanNav,
            $content
        );

        if (@file_put_contents($navComponentPath, $content) === false) {
            $this->output->writeln("<error>Failed to update navigation component file: $navComponentPath</error>");
            return;
        }

        $this->output->writeln("<info>Updated navigation component to show only Home link: $navComponentPath</info>");
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