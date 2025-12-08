<?php

namespace Noctalys\Cli\Command\MakeCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Noctalys\Cli\Utils\DotNoctalys;
use Noctalys\Cli\Utils\StringTransformer;

class PageCommand extends Command
{

    protected function configure(): void
    {
        $this
            ->setDescription('Generate a new page file')
            ->setHelp('This command allows you to create a new page file in the configured pages directory.')
            ->setName('make:page')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the page');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        if (!DotNoctalys::isNoctalysProject()) {
            $output->writeln("<error>This command must be run from within a Noctalys project directory</error>");
            return Command::FAILURE;
        }

        $name = $input->getArgument('name');
        $kebabName = StringTransformer::toKebabCase($name);
        $camelName = StringTransformer::toCamelCase($name);
        
        // Get template extension from .noctalys file
        $templateExtension = $this->getTemplateExtension($output);
        
        // Read config file to determine page location
        $configFile = getcwd() . '/config.json';
        if (!file_exists($configFile)) {
            $output->writeln("<error>Config file not found. Using default path.</error>");
            $targetDir = getcwd() . '/src/Pages';
        } else {
            $config = json_decode(file_get_contents($configFile), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $output->writeln("<error>Failed to parse config file. Using default path.</error>");
                $targetDir = getcwd() . '/src/Pages';
            } else {
                // Get page folder from router configuration
                $targetDir = $this->getPageDirectory($config, $output);
            }
        }
        
        // Create page directory with kebab-case name
        $pageDir = "$targetDir/$kebabName";
        if (!is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }
        
        // Create controller file
        $controllerPath = "$pageDir/$kebabName.controller.php";
        if (file_exists($controllerPath)) {
            $output->writeln("<comment>Controller file already exists at $controllerPath</comment>");
        } else {
            $controllerContent = $this->generateControllerTemplate($name, $camelName, $kebabName);
            file_put_contents($controllerPath, $controllerContent);
            $output->writeln("<info>Created controller file at $controllerPath</info>");
        }
        
        // Create view file with the appropriate extension
        $viewPath = "$pageDir/$kebabName.view$templateExtension";
        if (file_exists($viewPath)) {
            $output->writeln("<comment>View file already exists at $viewPath</comment>");
        } else {
            $viewContent = $this->generateViewTemplate($templateExtension);
            file_put_contents($viewPath, $viewContent);
            $output->writeln("<info>Created view file at $viewPath</info>");
        }
        
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
     * Get the directory where pages should be stored based on config
     * 
     * @param array $config The parsed config.json
     * @param OutputInterface $output Output interface for messages
     * @return string The directory path
     */
    private function getPageDirectory(array $config, OutputInterface $output): string
    {
        $projectRoot = getcwd();
        $defaultDir = "$projectRoot/src/Pages";
        
        if (!isset($config['router']) || !isset($config['router']['page_scan']) || empty($config['router']['page_scan'])) {
            $output->writeln("<comment>No page directory found in config. Using default.</comment>");
            return $defaultDir;
        }
        
        $pageScan = $config['router']['page_scan'];
        $currentDir = getcwd();
        $pageDirs = [];
        
        // Find all valid page directories based on config
        foreach ($pageScan as $source) {
            if (isset($source['folder_name']) && isset($source['path'])) {
                $parentPath = "$projectRoot/" . rtrim($source['path'], '/');
                $folderName = $source['folder_name'];
                
                // Find all directories with the specified name under the parent path
                $this->findDirectories($parentPath, $folderName, $pageDirs);
            }
        }
        
        // Check if current directory is one of the valid page directories
        foreach ($pageDirs as $pageDir) {
            if (strpos($currentDir, $pageDir) === 0) {
                $output->writeln("<info>Using current directory as it matches a configured page folder.</info>");
                return $currentDir;
            }
        }
        
        // If not in a page directory, use the first valid page directory found
        if (!empty($pageDirs)) {
            $selectedDir = $pageDirs[0];
            $output->writeln("<info>Using configured page directory: {$selectedDir}</info>");
            return $selectedDir;
        }
        
        $output->writeln("<comment>No valid page directory found in config. Using default.</comment>");
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
     * Generate the controller template content
     * 
     * @param string $name The page name in CamelCase
     * @param string $camelName The page name in CamelCase
     * @param string $kebabName The page name in kebab-case
     * @return string The controller template content
     */
    private function generateControllerTemplate(string $name, string $camelName, string $kebabName): string
    {
        return "<?php\n\nuse Noctalys\\Framework\\View\\View;\n\n/**\n * {$camelName} page controller\n */\nclass {$camelName}Controller\n{\n    public function main()\n    {\n        \$data = [\n            'title' => '{$name}',\n        ];\n        \n        View::render('{$kebabName}', \$data);\n    }\n}\n";
    }
    
    /**
     * Generate the view template content
     * 
     * @param string $extension The template file extension
     * @return string The view template content
     */
    private function generateViewTemplate(string $extension): string
    {        
        return "<!-- Page content goes here -->\n";
    }
}
