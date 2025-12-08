<?php

namespace Noctalys\Cli\Command\MakeCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Noctalys\Cli\Utils\DotNoctalys;
use Noctalys\Cli\Utils\StringTransformer;

class LayoutCommand extends Command
{
    protected static $defaultName = 'make:layout';

    protected function configure(): void
    {
        $this
            ->setDescription('Generate a new layout file')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the layout')
            ->setName('make:layout')
            ->setHelp('This command allows you to create a new layout file in the configured layouts directory.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!DotNoctalys::isNoctalysProject()) {
            $output->writeln("<error>This command must be run from within a Noctalys project directory</error>");
            return Command::FAILURE;
        }

        $name = $input->getArgument('name');
        $kebabName = StringTransformer::toKebabCase($name);
        
        // Get template extension from .noctalys file
        $templateExtension = $this->getTemplateExtension($output);
        
        // Read config file to determine layout location
        $configFile = getcwd() . '/config.json';
        if (!file_exists($configFile)) {
            $output->writeln("<error>Config file not found. Using default path.</error>");
            $targetDir = getcwd() . '/src/Layouts';
        } else {
            $config = json_decode(file_get_contents($configFile), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $output->writeln("<error>Failed to parse config file. Using default path.</error>");
                $targetDir = getcwd() . '/src/Layouts';
            } else {
                // Get layout folder from configuration
                $targetDir = $this->getLayoutDirectory($config, $output);
            }
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileName = $kebabName . $templateExtension;
        $filePath = "$targetDir/$fileName";
        
        if (file_exists($filePath)) {
            $output->writeln("<comment>Layout $fileName already exists at $filePath</comment>");
            return Command::SUCCESS;
        }
        
        $template = $this->generateLayoutTemplate($templateExtension);
        file_put_contents($filePath, $template);
        $output->writeln("<info>Created layout file at $filePath</info>");
        return Command::SUCCESS;
    }

    /**
     * Get the template extension from .noctalys file
     * 
     * @param OutputInterface $output Output interface for messages
     * @return string The template extension including the dot
     */
    private function getTemplateExtension(OutputInterface $output): string
    {
        $noctalysFile = getcwd() . '/.noctalys';
        $extension = '.php'; // Default extension
        
        if (file_exists($noctalysFile)) {
            $noctalysConfig = json_decode(file_get_contents($noctalysFile), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($noctalysConfig['templateExtension'])) {
                    $customExtension = $noctalysConfig['templateExtension'];
                    
                    // Make sure it starts with a dot
                    if (substr($customExtension, 0, 1) !== '.') {
                        $customExtension = '.' . $customExtension;
                    }
                    
                    $extension = $customExtension;
                    $output->writeln("<comment>Using template extension: $extension</comment>");
                }
            } else {
                $output->writeln("<error>Failed to parse .noctalys file. Using default extension (.php)</error>");
            }
        } else {
            $output->writeln("<comment>No .noctalys file found. Using default extension (.php)</comment>");
        }
        
        return $extension;
    }

    /**
     * Get the directory where layouts should be stored based on config
     * 
     * @param array $config The parsed config.json
     * @param OutputInterface $output Output interface for messages
     * @return string The directory path
     */
    private function getLayoutDirectory(array $config, OutputInterface $output): string
    {
        $projectRoot = getcwd();
        $defaultDir = "$projectRoot/src/Layouts";
        
        if (!isset($config['layouts']) || !isset($config['layouts']['sources']) || empty($config['layouts']['sources'])) {
            $output->writeln("<comment>No layout directory found in config. Using default.</comment>");
            return $defaultDir;
        }
        
        $layoutSources = $config['layouts']['sources'];
        $currentDir = getcwd();
        $layoutDirs = [];
        
        // Find all valid layout directories based on config
        foreach ($layoutSources as $source) {
            if (isset($source['folder_name']) && isset($source['path'])) {
                $parentPath = "$projectRoot/" . rtrim($source['path'], '/');
                $folderName = $source['folder_name'];
                
                // Find all directories with the specified name under the parent path
                $this->findDirectories($parentPath, $folderName, $layoutDirs);
            }
        }
        
        // Check if current directory is one of the valid layout directories
        foreach ($layoutDirs as $layoutDir) {
            if (strpos($currentDir, $layoutDir) === 0) {
                $output->writeln("<info>Using current directory as it matches a configured layout folder.</info>");
                return $currentDir;
            }
        }
        
        // If not in a layout directory, use the first valid layout directory found
        if (!empty($layoutDirs)) {
            $selectedDir = $layoutDirs[0];
            $output->writeln("<info>Using configured layout directory: {$selectedDir}</info>");
            return $selectedDir;
        }
        
        $output->writeln("<comment>No valid layout directory found in config. Using default.</comment>");
        return $defaultDir;
    }

    /**
     * Find all directories with a specific name within a parent directory
     *
     * @param string $parentDir The parent directory to search in
     * @param string $folderName The name of folders to find
     * @param array &$results Array to store found directories
     */
    private function findDirectories(string $parentDir, string $folderName, array &$results): void
    {
        if (!is_dir($parentDir)) {
            return;
        }
        
        // Check if the current directory matches the folder name
        if (basename($parentDir) === $folderName) {
            $results[] = $parentDir;
        }
        
        // Scan subdirectories
        $items = scandir($parentDir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $path = "$parentDir/$item";
            if (is_dir($path)) {
                $this->findDirectories($path, $folderName, $results);
                
                // Direct child match
                if ($item === $folderName) {
                    $results[] = $path;
                }
            }
        }
    }

    /**
     * Generate the layout template content
     * 
     * @param string $extension The template file extension
     * @return string The layout template content
     */
    private function generateLayoutTemplate(string $extension): string
    {
        // Add a simple comment depending on common template formats
        $comment = '';
        if ($extension === '.latte') {
            $comment = "{* Layout template *}\n\n";
        } elseif ($extension === '.twig') {
            $comment = "{# Layout template #}\n\n";
        } elseif (strpos($extension, '.blade') !== false) {
            $comment = "@php\n// Layout template\n@endphp\n\n";
        } elseif ($extension === '.mustache' || $extension === '.hbs') {
            $comment = "{{! Mustache template }}\n\n";
        } else {
            $comment = "<!-- Layout template -->\n\n";
        }
        
        // Generic layout structure
        return $comment . "<!DOCTYPE html>\n<html>\n<head>\n    <meta charset=\"UTF-8\">\n    <title>{{ \$title ?? 'Noctalys App' }}</title>\n</head>\n<body>\n    <main>\n        {{ \$content }}\n    </main>\n</body>\n</html>";
    }
}
