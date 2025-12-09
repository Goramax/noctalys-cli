<?php

namespace Noctalys\Cli\Command\Step\Init\Setup;
class InstallDependencies
{
	/**
	 * @param string|array $workingDir Absolute path as string, or an array containing path info.
	 * @param bool $optimizeAutoload Use optimized autoload (-o).
	 * @return int Exit code (0 on success).
	 */
	public function run($workingDir, bool $optimizeAutoload = true): int
	{
		$dir = $this->normalizeWorkingDir($workingDir);

		if ($dir === null || !is_dir($dir) || !is_file($dir . DIRECTORY_SEPARATOR . 'composer.json')) {
			$shown = is_array($workingDir) ? json_encode($workingDir) : (string)$workingDir;
			echo "[ERROR] Invalid working directory or missing composer.json: {$shown}\n";
			return 1;
		}

		$cmd = 'composer install' . ($optimizeAutoload ? ' -o' : '') . ' --working-dir=' . escapeshellarg($dir);

		// passthru streams output directly and returns the exit status via $exitCode
		$exitCode = 0;
		passthru($cmd, $exitCode);

		if ($exitCode === 0) {
			echo "[OK] Dependencies installed.\n";
		} else {
			echo "[ERROR] Composer install failed with exit code {$exitCode}.\n";
		}

		return $exitCode;
	}

	/**
	 * Normalize working directory argument to a string path.
	 * Accepts:
	 * - string path
	 * - array with keys ['path'] or ['workingDir'] or first numeric element
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
			// common in setup payloads: 'target' absolute path
			if (isset($workingDir['target']) && is_string($workingDir['target'])) {
				return $workingDir['target'];
			}
			// first element
			$first = reset($workingDir);
			if (is_string($first)) {
				return $first;
			}
		}
		return null;
	}
}

