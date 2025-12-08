<?php

namespace Noctalys\Cli\Utils;

use Symfony\Component\Console\Output\OutputInterface;

class TemplateConverter
{
    private string $engine;
    private string $inputDir;
    private string $outputDir;
    private array $engineData;
    private string $engineFile;
    private string $cssFramework;
    private string $cssFrameworkFile;
    private ?array $cssFrameworkData = null;
    private OutputInterface $output;

    /**
     * Create a new template converter
     * 
     * @param string $engine Template engine to use
     * @param string $cssFramework CSS framework to use
     * @param string $inputDir Directory containing template files
     * @param string $outputDir Directory for converted files
     * @param OutputInterface $output Output interface for messages
     */
    public function __construct(string $engine, string $cssFramework, string $inputDir, string $outputDir, OutputInterface $output)
    {
        $this->engine = $engine;
        $this->cssFramework = $cssFramework;
        $this->inputDir = rtrim($inputDir, '/');
        $this->outputDir = rtrim($outputDir, '/');   
        $this->output = $output;
        
        $repoRoot = $this->inputDir;
        $enginePath = "{$repoRoot}/engines/{$engine}.json";
        $cssFrameworkPath = "{$repoRoot}/css-frameworks/{$cssFramework}.json";
        
        $this->output->writeln("Looking for engine at: {$enginePath}");
        
        $this->engineFile = $enginePath;
        $this->cssFrameworkFile = $cssFrameworkPath;
    }

    /**
     * Convert templates from input directory to output directory
     * 
     * @return bool True if conversion was successful
     */
    public function convert(): bool
    {
        if (!$this->loadEngineData()) {
            return false;
        }
        
        if ($this->cssFramework !== 'none' && !$this->loadCssFrameworkData()) {
            return false;
        }

        if (!is_dir($this->outputDir)) {
            if (!mkdir($this->outputDir, 0755, true)) {
                $this->output->writeln("<error>Error: Failed to create output directory</error>");
                return false;
            }
        }

        $templatesDir = "{$this->inputDir}/templates";
        if (!is_dir($templatesDir)) {
            $this->output->writeln("<error>Error: Templates directory not found at: {$templatesDir}</error>");
            return false;
        }

        $this->processDirectory($templatesDir, $this->outputDir);
        $this->output->writeln("Conversion completed successfully!");
        
        return true;
    }
    
    /**
     * Apply converted templates to a target project
     *
     * @param string $targetProjectDir Target project directory
     * @param array $replacements Optional array of key-value pairs to replace in the templates
     * @return bool True if application was successful
     */
    public function applyToProject(string $targetProjectDir, array $replacements = []): bool
    {
        $targetProjectDir = rtrim($targetProjectDir, '/');
        
        if (!is_dir($targetProjectDir)) {
            $this->output->writeln("<error>Error: Target project directory does not exist: {$targetProjectDir}</error>");
            return false;
        }
        
        if (!is_dir($this->outputDir)) {
            $this->output->writeln("<error>Error: Converted templates directory does not exist. Run convert() first.</error>");
            return false;
        }
        
        $this->output->writeln("Applying templates to project: {$targetProjectDir}");
        
        $targetFrontendDir = "{$targetProjectDir}/src/Frontend";
        if (!is_dir($targetFrontendDir)) {
            if (!mkdir($targetFrontendDir, 0755, true)) {
                $this->output->writeln("<error>Error: Failed to create Frontend directory in target project</error>");
                return false;
            }
        }
        
        if (!$this->copyWithReplacementsAndCleanConflicts($this->outputDir, $targetFrontendDir, $replacements)) {
            $this->output->writeln("<error>Error: Failed to apply templates to project</error>");
            return false;
        }
        
        $this->output->writeln("Templates successfully applied to project!");
        return true;
    }

    /**
     * Replace config file by config template
     * @param string $type The project type (Frontend, Backend, Mixed)
     * @param string $targetProjectDir The target project directory where the config file should be replaced
     * @return bool True if the config file was replaced successfully
     */
    public function replaceConfigFile(string $type, string $targetProjectDir): bool
    {
        $type = strtolower($type);

        $valid = ['frontend', 'backend', 'mixed'];
        if (!in_array($type, $valid, true)) {
            $this->output->writeln("<error>Error: Invalid project type specified: {$type}</error>");
            return false;
        }

        $configFile = "{$this->inputDir}/configs/{$type}.config.json";
        if (!file_exists($configFile)) {
            $this->output->writeln("<error>Error: Config file not found for type '{$type}' at: {$configFile}</error>");
            return false;
        }

        $targetConfigFile = rtrim($targetProjectDir, '/').'/config.json';
        if (!copy($configFile, $targetConfigFile)) {
            $this->output->writeln("<error>Error: Failed to copy config file to {$targetConfigFile}</error>");
            return false;
        }

        $this->output->writeln("Config file replaced successfully for type '{$type}'.");
        return true;
    }
    
    /**
     * Load and validate engine configuration data
     * 
     * @return bool True if engine data is valid
     */
    private function loadEngineData(): bool
    {
        if (!file_exists($this->engineFile)) {
            $this->output->writeln("<error>Error: Engine '{$this->engine}' not found.</error>");
            return false;
        }

        $this->engineData = json_decode(file_get_contents($this->engineFile), true);
        if (!$this->engineData || !isset($this->engineData['mappings'])) {
            $this->output->writeln("<error>Error: Invalid engine mapping file</error>");
            return false;
        }

        if (!isset($this->engineData['file_extension'])) {
            $this->output->writeln("Warning: No file_extension defined in engine file, using '.html' as default");
            $this->engineData['file_extension'] = '.html';
        }

        return true;
    }
    
    /**
     * Load and validate CSS framework configuration data
     * 
     * @return bool True if CSS framework data is valid
     */
    private function loadCssFrameworkData(): bool
    {
        if (empty($this->cssFramework) || $this->cssFramework === 'none') {
            return true;
        }
        
        if (!file_exists($this->cssFrameworkFile)) {
            $this->output->writeln("<error>Error: CSS framework '{$this->cssFramework}' not found.</error>");
            return false;
        }

        $this->cssFrameworkData = json_decode(file_get_contents($this->cssFrameworkFile), true);
        if (!$this->cssFrameworkData || !isset($this->cssFrameworkData['mappings'])) {
            $this->output->writeln("<error>Error: Invalid CSS framework mapping file</error>");
            return false;
        }

        return true;
    }

    /**
     * Process all template files in a directory recursively
     * 
     * @param string $inputDir Current input directory being processed
     * @param string $outputDir Current output directory to write to
     */
    private function processDirectory(string $inputDir, string $outputDir): void
    {
        $items = scandir($inputDir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $inputPath = "{$inputDir}/{$item}";
            $outputPath = "{$outputDir}/{$item}";
            
            if (is_dir($inputPath)) {
                if (!is_dir($outputPath) && !mkdir($outputPath, 0755, true)) {
                    $this->output->writeln("<error>Error: Failed to create directory: {$outputPath}</error>");
                    continue;
                }
                
                $this->processDirectory($inputPath, $outputPath);
            } else {
                if (pathinfo($inputPath, PATHINFO_EXTENSION) === 'html') {
                    $this->convertTemplateFile($inputPath, $outputPath);
                } else {
                    copy($inputPath, $outputPath);
                }
            }
        }
    }

    /**
     * Convert a single template file
     * 
     * @param string $inputPath Source template file path
     * @param string $outputPath Destination converted file path
     */
    private function convertTemplateFile(string $inputPath, string $outputPath): void
    {
        $content = file_get_contents($inputPath);
        if ($content === false) {
            $this->output->writeln("<error>Error: Failed to read file: {$inputPath}</error>");
            return;
        }
        
        $content = $this->applyEngineMappings($content);
        
        if ($this->cssFrameworkData !== null) {
            $content = $this->applyCssFrameworkMappings($content);
        }
        
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                $this->output->writeln("<error>Error: Failed to create directory: {$outputDir}</error>");
                return;
            }
        }
        
        $outputPath = preg_replace('/\.stub\.html$/', $this->getFileExtension(), $outputPath);
        
        if (file_put_contents($outputPath, $content) === false) {
            $this->output->writeln("<error>Error: Failed to write file: {$outputPath}</error>");
            return;
        }
        
        $this->output->writeln("Converted: {$inputPath} → {$outputPath}");
    }
    
    /**
     * Apply template engine mappings to content
     *
     * @param string $content The template content
     * @return string The transformed content
     */
    private function applyEngineMappings(string $content): string
    {
        foreach ($this->engineData['mappings'] as $mapping) {
            $pattern = $mapping['pattern'];
            $replacement = $mapping['replacement'];

            $content = preg_replace("#$pattern#s", $replacement, $content);
        }
        return $content;
    }

    /**
     * Apply CSS framework mappings to content
     *
     * @param string $content The template content
     * @return string The transformed content
     */
    private function applyCssFrameworkMappings(string $content): string
    {
        foreach ($this->cssFrameworkData['mappings'] as $mapping) {
            $pattern = $mapping['pattern'];
            $replacement = $mapping['replacement'];

            $content = preg_replace("#$pattern#s", $replacement, $content);
        }
        return $content;
    }

    /**
     * Get the appropriate file extension based on the engine
     * 
     * @return string File extension including the leading dot
     */
    private function getFileExtension(): string
    {
        return $this->engineData['file_extension'];
    }
    
    /**
     * Copy files with text replacements and remove existing files with same basename
     *
     * @param string $source Source directory
     * @param string $destination Destination directory
     * @param array $replacements Key-value pairs for replacements in text files
     * @return bool True if copy was successful
     */
    private function copyWithReplacementsAndCleanConflicts(string $source, string $destination, array $replacements): bool
    {
        $items = scandir($source);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $sourcePath = "{$source}/{$item}";
            $destinationPath = "{$destination}/{$item}";
            
            if (is_dir($sourcePath)) {
                if (!is_dir($destinationPath) && !mkdir($destinationPath, 0755, true)) {
                    $this->output->writeln("<error>Error: Failed to create directory: {$destinationPath}</error>");
                    return false;
                }
                
                if (!$this->copyWithReplacementsAndCleanConflicts($sourcePath, $destinationPath, $replacements)) {
                    return false;
                }
            } else {
                $baseName = pathinfo($item, PATHINFO_FILENAME);
                $destDir = dirname($destinationPath);
                
                if (is_dir($destDir)) {
                    $existingFiles = scandir($destDir);
                    foreach ($existingFiles as $existingFile) {
                        $existingBaseName = pathinfo($existingFile, PATHINFO_FILENAME);
                        if ($existingBaseName === $baseName && $existingFile !== $item) {
                            $fullPath = "{$destDir}/{$existingFile}";
                            if (is_file($fullPath)) {
                                $this->output->writeln("Removing conflicting file: {$fullPath}");
                                unlink($fullPath);
                            }
                        }
                    }
                }
                
                $content = file_get_contents($sourcePath);
                if ($content === false) {
                    $this->output->writeln("<error>Error: Failed to read file: {$sourcePath}</error>");
                    return false;
                }
                
                if ($this->isTextFile($sourcePath)) {
                    foreach ($replacements as $search => $replace) {
                        $content = str_replace($search, $replace, $content);
                    }
                }
                
                if (file_put_contents($destinationPath, $content) === false) {
                    $this->output->writeln("<error>Error: Failed to write file: {$destinationPath}</error>");
                    return false;
                }
                
                $this->output->writeln("Applied: {$sourcePath} → {$destinationPath}");
            }
        }
        
        return true;
    }
    
    /**
     * Check if a file is a text file
     *
     * @param string $filePath File path to check
     * @return bool True if it's a text file
     */
    private function isTextFile(string $filePath): bool
    {
        $textExtensions = [
            'php', 'html', 'htm', 'js', 'css', 'scss', 'sass', 'less',
            'json', 'xml', 'yml', 'yaml', 'md', 'txt', 'csv',
            'ini', 'conf', 'env',
            'twig', 'latte', 'blade', 'smarty', 'mustache', 'phtml', 'volt', 'liquid'
        ];
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if (in_array($extension, $textExtensions)) {
            return true;
        }
        
        if (file_exists($filePath)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($filePath);
            if (strpos($mime, 'text/') === 0) {
                return true;
            }
            
            $content = @file_get_contents($filePath, false, null, 0, 1000);
            if ($content !== false && mb_detect_encoding($content, 'UTF-8', true)) {
                return true;
            }
        }
        
        if (isset($this->engineData['file_extension'])) {
            $engineExt = ltrim($this->engineData['file_extension'], '.');
            if ($extension === $engineExt) {
                return true;
            }
        }
        
        if (file_exists($filePath) && filesize($filePath) < 1024 * 50) {
            return true;
        }
        
        return false;
    }
}
