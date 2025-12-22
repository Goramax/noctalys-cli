<?php

namespace Noctalys\Cli\Command\Step\Init\Setup;

use Noctalys\Cli\Command\Step\StepInterface;

class InstallDependencies implements StepInterface
{
	/**
	 * Install project dependencies and add the selected template engine package.
	 */
	public function run(array &$context): bool
	{
		$dir = $this->normalizeWorkingDir($context['target'] ?? null);
		if ($dir === null || !is_dir($dir) || !is_file($dir . DIRECTORY_SEPARATOR . 'composer.json')) {
			$shown = is_array($context['target'] ?? null) ? json_encode($context['target']) : (string)($context['target'] ?? '');
			echo "[ERROR] Invalid working directory or missing composer.json: {$shown}\n";
			return false;
		}

		$optimizeAutoload = true;
		if (!$this->runComposerCommand('install' . ($optimizeAutoload ? ' -o' : ''), $dir)) {
			return false;
		}

		$engine = strtolower($context['template-engine'] ?? '');
		$package = $this->mapEngineToPackage($engine);
		if ($package !== null) {
			// Install the chosen template engine into the target project
			if (!$this->runComposerCommand('require ' . escapeshellarg($package), $dir)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Map a template engine name to its Composer package.
	 */
	private function mapEngineToPackage(string $engine): ?string
	{
		return match ($engine) {
			'latte'  => 'latte/latte',
			'twig'   => 'twig/twig',
			'smarty' => 'smarty/smarty',
			default => null,
		};
	}

	/**
	 * Run a composer command in the given working directory.
	 */
	private function runComposerCommand(string $command, string $workingDir): bool
	{
		$cmd = 'composer ' . $command . ' --working-dir=' . escapeshellarg($workingDir);
		$exitCode = 0;
		passthru($cmd, $exitCode);
		return $exitCode === 0;
	}

	/**
	 * Normalize working directory argument to a string path.
	 */
	private function normalizeWorkingDir($workingDir): ?string
	{
		if (is_string($workingDir)) {
			return $workingDir;
		}
		if (is_array($workingDir)) {
			if (isset($workingDir['path']) && is_string($workingDir['path'])) {
				return $workingDir['path'];
			}
			if (isset($workingDir['workingDir']) && is_string($workingDir['workingDir'])) {
				return $workingDir['workingDir'];
			}
			if (isset($workingDir['target']) && is_string($workingDir['target'])) {
				return $workingDir['target'];
			}
			$first = reset($workingDir);
			if (is_string($first)) {
				return $first;
			}
		}
		return null;
	}
}

