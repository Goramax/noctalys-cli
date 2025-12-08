<?php

namespace Noctalys\Cli\Command\Step\Init\Setup;

use Noctalys\Cli\Command\Step\StepInterface;
use Noctalys\Cli\Utils\ConfigManager;

class NoctalysFile implements StepInterface
{
    /**
     * Set up the .noctalys configuration file for the project
     * 
     * @param array &$context Context data containing project information
     * @return bool True if the file was created successfully
     */
    public function run(array &$context): bool
    {
        $output    = $context['output'];
        $target    = $context['target'];
        $namespace = $context['name_camel'] ?? 'NoctalysDemoApp';
        $projectType = $context['type'] ?? 'Mixed';

        // Get or validate framework version
        $requested = $context['frameworkVersion'] ?? null;
        if ($requested) {
            $tags = $this->getNoctalysTags();
            if (!in_array($requested, $tags, true)) {
                $output->writeln("<error>Version '{$requested}' not found. Available versions: " . implode(', ', $tags) . "</error>");
                return false;
            }
            $frameworkVersion = $requested;
        } else {
            $frameworkVersion = $this->getLatestNoctalysTag();
        }

        $configData = [
            'namespace'        => $namespace,
            'projectType'      => $projectType,
            'creationDate'     => date('Y-m-d H:i:s'),
            'frameworkVersion' => $frameworkVersion,
        ];

        // Add template engine info if available
        $engine = strtolower($context['template-engine'] ?? '');
        if ($engine && $engine !== 'none') {
            $engineFile = rtrim($target, '/') . '/../noctalys-starter-templates/engines/' . $engine . '.json';
            if (file_exists($engineFile)) {
                $engineConfig = ConfigManager::load($engineFile);
                if ($engineConfig !== null) {
                    $ext = ConfigManager::get($engineConfig, 'file_extension', '');
                    $configData['templateEngine'] = $engine;
                    $configData['templateExtension'] = $ext;
                }
            }
        }

        $noctalysPath = rtrim($target, '/') . '/.noctalys';

        if (!ConfigManager::save($noctalysPath, $configData)) {
            $output->writeln("<error>Failed to write .noctalys configuration file</error>");
            return false;
        }

        if (!file_exists($noctalysPath)) {
            $output->writeln("Created .noctalys configuration file at <info>$noctalysPath</info>");
        } else {
            $output->writeln("<comment>Updated .noctalys configuration file at <info>$noctalysPath</info></comment>");
        }

        return true;
    }

    /**
     * Fetch all Noctalys tags from GitHub
     *
     * @return string[]
     */
    private function getNoctalysTags(): array
    {
        $tagsUrl = 'https://api.github.com/repos/Goramax/Noctalys/tags';
        $opts = ["http" => ["method" => "GET", "header" => "User-Agent: NoctalysCli\r\n"]];
        $ctx  = stream_context_create($opts);
        $json = @file_get_contents($tagsUrl, false, $ctx);
        if ($json !== false) {
            $items = json_decode($json, true);
            if (is_array($items)) {
                return array_map(fn($t) => $t['name'] ?? '', $items);
            }
        }
        return [];
    }

    /**
     * Get the latest Noctalys tag
     *
     * @return string
     */
    private function getLatestNoctalysTag(): string
    {
        $tags = array_filter($this->getNoctalysTags());
        if (empty($tags)) {
            return '0.1.0';
        }
        usort($tags, fn($a, $b) => version_compare($b, $a));
        return $tags[0];
    }
}