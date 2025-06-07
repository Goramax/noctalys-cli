<?php

namespace Goramax\NoctalysCli\Command\Step\Init\Setup;

use Goramax\NoctalysCli\Command\Step\StepInterface;
use Goramax\NoctalysCli\Utils\ConfigManager;

class NoctalysFile implements StepInterface
{
    public function run(array &$context): bool
    {
        $output      = $context['output'];
        $target      = $context['target'];
        $namespace   = $context['name_camel'] ?? 'NoctalysDemoApp';
        $projectType = $context['type'] ?? 'Mixed';

        // Get framework version from context or fetch from GitHub tags
        $frameworkVersion = $context['frameworkVersion'] ?? $this->getLatestNoctalysTag();

        $configData = [
            'namespace'        => $namespace,
            'projectType'      => $projectType,
            'creationDate'     => date('Y-m-d H:i:s'),
            'frameworkVersion' => $frameworkVersion,
        ];

        // if project has a template engine, add the name and extension to the config
        $engine = strtolower($context['template-engine'] ?? '');
        if ($engine && $engine !== 'none') {
            // assume starter-templates lives one level up from target
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

    private function getLatestNoctalysTag(): string
    {
        $tagsUrl = 'https://api.github.com/repos/Goramax/Noctalys/tags';
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: NoctalysCli\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $json = @file_get_contents($tagsUrl, false, $context);
        if ($json !== false) {
            $tags = json_decode($json, true);
            if (is_array($tags) && count($tags) > 0 && isset($tags[0]['name'])) {
                return $tags[0]['name'];
            }
        }
        return '0.1.0';
    }
}