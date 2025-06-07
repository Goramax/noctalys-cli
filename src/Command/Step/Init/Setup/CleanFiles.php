<?php

namespace Goramax\NoctalysCli\Command\Step\Init\Setup;

use Goramax\NoctalysCli\Command\Step\StepInterface;

class CleanFiles implements StepInterface
{
    public function run(array &$context): bool
    {
        $output = $context['output'];
        $target = $context['target'];
        
        // Get type from context instead of input options
        $type = isset($context['type']) ? strtolower($context['type']) : '';
        
        $output->writeln("Cleaning files for type: <info>$type</info>");
        if ($type === 'frontend') {
            $backendDir = $target . '/src/Backend';
            if (is_dir($backendDir)) {
                exec("rm -rf " . escapeshellarg($backendDir), $rmOutput, $rmCode);
                if ($rmCode !== 0) {
                    $output->writeln("<error>Failed to remove Backend directory</error>");
                    return false;
                }
                $output->writeln("Removed Backend directory: <info>$backendDir</info>");
            }
        } elseif ($type === 'backend') {
            $frontendDir = $target . '/src/Frontend';
            if (is_dir($frontendDir)) {
                exec("rm -rf " . escapeshellarg($frontendDir), $rmOutput, $rmCode);
                if ($rmCode !== 0) {
                    $output->writeln("<error>Failed to remove Frontend directory</error>");
                    return false;
                }
                $output->writeln("Removed Frontend directory: <info>$frontendDir</info>");
            } else {
                $output->writeln("No Frontend directory to remove for backend-only project.");
            }
            
            // Also remove styles directory for backend-only projects
            $stylesDir = $target . '/public/assets/css';
            if (is_dir($stylesDir)) {
                exec("rm -rf " . escapeshellarg($stylesDir), $rmOutput, $rmCode);
                if ($rmCode !== 0) {
                    $output->writeln("<error>Failed to remove styles directory</error>");
                    return false;
                }
                $output->writeln("Removed styles directory: <info>$stylesDir</info>");
            }
            else {
                $output->writeln("No styles directory to remove for backend-only project.");
            }
        }
        
        // Remove temporary directory if it exists
        $tmpDir = $target . '/.tmp';
        if (is_dir($tmpDir)) {
            $output->writeln("<comment>Removing temporary directory...</comment>");
            exec("rm -rf " . escapeshellarg($tmpDir), $rmOutput, $rmCode);
            if ($rmCode !== 0) {
                $output->writeln("<error>Failed to remove temporary directory</error>");
                // Don't return false here, consider it a non-critical error
            } else {
                $output->writeln("<info>Removed temporary directory: $tmpDir</info>");
            }
        }
        
        return true;
    }
}
