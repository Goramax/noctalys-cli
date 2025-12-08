<?php

namespace Noctalys\Cli\Command\Step\Init\Setup;
use Noctalys\Cli\Command\Step\StepInterface;
use Noctalys\Cli\Utils\TemplateConverter;

class TemplateFilesSetup implements StepInterface
{
    /** Path to local starter templates (relative to project root) */
    private string $localTemplatePath = '';

    /**
     * Run template files setup step
     * 
     * @param array &$context Context data containing project information
     * @return bool True if templates were set up successfully
     */
    public function run(array &$context): bool
    {
        $output = $context['output'];
        $output->writeln('<comment>Setting up template files...</comment>');
        
        $templateEngine = strtolower($context['template-engine'] ?? 'php');
        $cssFramework = strtolower($context['css-framework'] ?? 'none');
        
        $projectDir = $context['target'] ?? getcwd();
        
        $this->localTemplatePath = $projectDir . '/../noctalys-starter-templates';
        $output->writeln("----- TemplateFilesSetup -----");
        $output->writeln($this->localTemplatePath);

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
            $repoUrl         = 'https://github.com/Goramax/noctalys-starter-templates.git';
            $templateRepoDir = $tmpDir . '/noctalys-starter-templates';
            $output->writeln("<comment>Cloning template repository...</comment>");
            exec("git clone $repoUrl $templateRepoDir 2>&1", $cmdOutput, $returnCode);

            if ($returnCode !== 0) {
                $output->writeln("<error>Failed to clone the template repository: " . implode("\n", $cmdOutput) . "</error>");
                return false;
            }
            
            $output->writeln("<info>Template repository cloned successfully.</info>");
        }
        
        $targetDir = $projectDir . '/src/Frontend';
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                $output->writeln("<error>Failed to create target directory: $targetDir</error>");
                return false;
            }
        }
        
        $output->writeln(sprintf("<comment>Converting templates for %s with %s CSS framework...</comment>", 
            $templateEngine, 
            $cssFramework !== 'none' ? $cssFramework : 'no'
        ));
        
        try {
            $templatesSourceDir = $templateRepoDir;
            $templatesDestDir = $tmpDir . '/converted_templates';
            
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
            
            $output->writeln("<comment>Applying converted templates to project directory...</comment>");
            
            $success = $converter->applyToProject($projectDir);
            
            if (!$success) {
                $output->writeln("<error>Failed to apply templates to project</error>");
                return false;
            }

            $success = $converter->replaceConfigFile($context['type'], $projectDir);
            if (!$success) {
                $output->writeln("<error>Failed to apply config file template</error>");
                return false;
            }
            
            $output->writeln("<info>Template files setup completed successfully</info>");
            
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
     * Recursively remove directory and its contents
     * 
     * @param string $dir Directory to remove
     * @return bool True if directory was removed successfully
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