<?php

namespace Noctalys\Cli\Utils;

class DotNoctalys
{
    /**
     * Check if the .noctalys file exists in the current directory or any parent directory
     * 
     * @return bool True if the file exists, false otherwise
     */
    public static function isNoctalysProject(int $maxDepth = 25): bool
    {
        $currentDir = getcwd();
        $depth = 0;

        while ($currentDir !== '/' && $depth < $maxDepth) {
            if (file_exists($currentDir . '/.noctalys')) {
                return true;
            }
            $currentDir = dirname($currentDir);
            $depth++;
        }

        return false;
    }
}
