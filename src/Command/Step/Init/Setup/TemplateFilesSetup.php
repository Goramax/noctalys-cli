<?php

namespace Goramax\NoctalysCli\Command\Step\Init\Setup;
use Goramax\NoctalysCli\Command\Step\StepInterface;
use Goramax\NoctalysCli\Utils\TemplateConverter;

class TemplateFilesSetup implements StepInterface
{
    /** Path to local starter templates (relative to project root) */
    private string $localTemplatePath = '';

    public function run(array &$context): bool
    {
        $output = $context['output'];
        $output->writeln('<comment>Setting up template files...</comment>');
        
        $templateEngine = strtolower($context['template-engine'] ?? 'php');
        $cssFramework = strtolower($context['css-framework'] ?? 'none');
        
        // Use target from context which has the complete project path
        $projectDir = $context['target'] ?? getcwd();
        
        $this->localTemplatePath = $projectDir . '/../noctalys-starter-templates';
        $output->writeln("----- TemplateFilesSetup -----");
        $output->writeln($this->localTemplatePath);

        // Create temp directory for cloning
        $tmpDir = $projectDir . '/.tmp';
        if (!is_dir($tmpDir)) {
            if (!mkdir($tmpDir, 0755, true)) {
                $output->writeln("<error>Failed to create temporary directory: $tmpDir</error>");
                return false;
            }
        }
        
        if (is_dir($this->localTemplatePath)) {
            $templateRepoDir = $this->localTemplatePath;
            $output->writeln("<comment>Using local template directory: {$this->localTemplatePath}</comment>");
        } else {
            // Clone the template repository
            $repoUrl         = 'https://github.com/Goramax/noctalys-starter-templates.git';
            $templateRepoDir = $tmpDir . '/noctalys-starter-templates';
            $output->writeln("<comment>Cloning template repository...</comment>");
            exec("git clone $repoUrl $templateRepoDir 2>&1", $cmdOutput, $returnCode);

            // Check if clone was successful
            if ($returnCode !== 0) {
                $output->writeln("<error>Failed to clone the template repository: " . implode("\n", $cmdOutput) . "</error>");
                return false;
            }
            
            $output->writeln("<info>Template repository cloned successfully.</info>");
        }
        
        // Set up target directories
        $targetDir = $projectDir . '/src/Frontend';
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                $output->writeln("<error>Failed to create target directory: $targetDir</error>");
                return false;
            }
        }
        
        // Convert templates using TemplateConverter
        $output->writeln(sprintf("<comment>Converting templates for %s with %s CSS framework...</comment>", 
            $templateEngine, 
            $cssFramework !== 'none' ? $cssFramework : 'no'
        ));
        
        try {
            // Templates source and destination
            $templatesSourceDir = $templateRepoDir;
            $templatesDestDir = $tmpDir . '/converted_templates';
            
            // Initialize and run the converter
            $converter = new TemplateConverter(
                $templateEngine, 
                $cssFramework, 
                $templatesSourceDir, 
                $templatesDestDir,
                $output
            );
            
            $success = $converter->convert();
            
            if (!$success) {
                $output->writeln("<error>Failed to convert templates</error>");
                return false;
            }
            
            // Apply converted templates to project with replacements
            $output->writeln("<comment>Applying converted templates to project directory...</comment>");
            
            // Pass the correct projectDir to applyToProject
            $success = $converter->applyToProject($projectDir);
            
            if (!$success) {
                $output->writeln("<error>Failed to apply templates to project</error>");
                return false;
            }

            // Apply config file template
            $success = $converter->replaceConfigFile($context['type'], $projectDir);
            if (!$success) {
                $output->writeln("<error>Failed to apply config file template</error>");
                return false;
            }
            
            $output->writeln("<info>Template files setup completed successfully</info>");
            
            // Clean up temporary files if needed
            if (isset($context['cleanup_tmp']) && $context['cleanup_tmp'] === true) {
                $output->writeln("<comment>Cleaning up temporary files...</comment>");
                $this->recursiveRemoveDir($tmpDir);
            }
            
            return true;
        } catch (\Exception $e) {
            $output->writeln("<error>An error occurred during template conversion: " . $e->getMessage() . "</error>");
            return false;
        }
    }
    
    /**
     * Recursively copy files and directories
     */
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
     * Recursively remove directory and its contents
     */
    private function recursiveRemoveDir(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->recursiveRemoveDir($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
}