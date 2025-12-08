<?php

namespace Noctalys\Cli\Command\Step\Init\Setup;

use Noctalys\Cli\Command\Step\StepInterface;

class CleanFiles implements StepInterface
{
    private const PAGES_PATHS      = ['/src/Frontend/pages', '/src/pages'];
    private const LAYOUTS_PATHS    = ['/src/Frontend/layouts', '/src/layouts'];
    private const COMPONENTS_PATHS = ['/src/Frontend/components', '/src/components'];
    private const STYLES_PATHS     = ['/src/Frontend/assets/styles', '/src/assets/styles'];

    /**
     * Run file cleanup based on project type and base project
     * 
     * @param array &$context Context data containing project information
     * @return bool True if cleanup was successful
     */
    public function run(array &$context): bool
    {
        $output = $context['output'];
        $target = $context['target'];
        $type = strtolower($context['type'] ?? '');
        $base = strtolower($context['base_project'] ?? '');

        $output->writeln("Cleaning for type: <info>$type</info>");

        if ($type === 'frontend') {
            $this->removeIfExists("$target/src/Backend", 'Backend directory', $output);
        } elseif ($type === 'backend') {
            $this->removeIfExists("$target/src/Frontend", 'Frontend directory', $output);
            $this->removeIfExists("$target/public/assets/css", 'styles directory', $output);
        }

        $this->removeIfExists("$target/.tmp", 'temporary directory', $output, false);

        if (in_array($type, ['frontend','mixed'], true) && $base === 'minimal') {
            $output->writeln("<comment>Pruning Minimal pages/components/styles…</comment>");
            $this->prunePagesAndComponents($target);
            $this->pruneScss($target);
            $this->cleanMainScss($target);
        }

        return true;
    }

    /**
     * Remove directory if it exists
     * 
     * @param string $path Path to directory
     * @param string $label Label for logging
     * @param OutputInterface $output Output interface for logging
     * @param bool $failOnError Whether to log errors when removal fails
     * @return void
     */
    private function removeIfExists(string $path, string $label, $output, bool $failOnError = true): void
    {
        if (!is_dir($path)) {
            return;
        }
        if ($this->rrmdir($path)) {
            $output->writeln("Removed $label: <info>$path</info>");
        } elseif ($failOnError) {
            $output->writeln("<error>Failed to remove $label: $path</error>");
        }
    }

    /**
     * Prune unnecessary pages and components for minimal projects
     * 
     * @param string $target Project directory
     * @return void
     */
    private function prunePagesAndComponents(string $target): void
    {
        // resolve a single existing directory from a list
        $resolve = fn(array $list) => array_reduce(
            $list, fn($carry, $path) => $carry ?: (is_dir($target.$path) ? $target.$path : null),
            null
        );

        $pages      = $resolve(self::PAGES_PATHS);
        $layouts    = $resolve(self::LAYOUTS_PATHS);
        $components = $resolve(self::COMPONENTS_PATHS);

        if ($pages) {
            foreach (glob("$pages/*", GLOB_ONLYDIR) as $dir) {
                if (basename($dir) !== 'home') {
                    $this->rrmdir($dir);
                }
            }
        }

        $used = [];
        $files = array_merge(
            $pages      ? glob("$pages/home/*") : [],
            $layouts    ? glob("$layouts/default.layout.*") : []
        );

        foreach ($files as $f) {
            preg_match_all("/render_component\(\s*'([^']+)'/", @file_get_contents($f) ?: '', $m);
            $used = array_unique(array_merge($used, $m[1] ?? []));
        }

        if ($components) {
            foreach (glob("$components/*.component.*") as $file) {
                if (preg_match('/^([^\.]+)\.component\./', basename($file), $m)
                    && !in_array($m[1], $used, true)
                ) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Prune SCSS files for minimal projects
     * 
     * @param string $target Project directory
     * @return void
     */
    private function pruneScss(string $target): void
    {
        foreach (self::STYLES_PATHS as $p) {
            $dir = $target . $p;
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob("$dir/*.scss") as $f) {
                if (!in_array(basename($f), ['_variables.scss','_animations.scss','main.scss'], true)) {
                    unlink($f);
                }
            }
        }
    }

    /**
     * Remove @use statements in main.scss for deleted partials
     * 
     * @param string $target Project directory
     * @return void
     */
    private function cleanMainScss(string $target): void
    {
        foreach (self::STYLES_PATHS as $p) {
            $dir  = $target . $p;
            $file = $dir . '/main.scss';
            if (!is_file($file)) {
                continue;
            }
            $lines = file($file, FILE_IGNORE_NEW_LINES);
            $keep  = array_filter($lines, function(string $line): bool {
                if (preg_match('/^\s*@use/i', $line)) {
                    // only keep variables and animations imports
                    return preg_match('/variables|animations/i', $line);
                }
                return true;
            });
            file_put_contents($file, implode(PHP_EOL, $keep) . PHP_EOL);
        }
    }

    /**
     * Recursively remove directory and all contents
     * 
     * @param string $dir Directory to remove
     * @return bool True if directory was removed
     */
    private function rrmdir(string $dir): bool
    {
        foreach (array_diff(scandir($dir), ['.','..']) as $e) {
            $p = "$dir/$e";
            is_dir($p) ? $this->rrmdir($p) : @unlink($p);
        }
        return @rmdir($dir);
    }
}
