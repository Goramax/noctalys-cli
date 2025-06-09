<?php

namespace Goramax\NoctalysCli\Utils;

class StringTransformer
{
    /**
     * Convert a string to kebab-case
     */
    public static function toKebabCase(string $input): string
    {
        $input = trim($input, '"\'');
        $kebabCase = preg_replace('/[\s_]+/', '-', $input);
        $kebabCase = preg_replace('/[^a-z0-9-]/', '', strtolower($kebabCase));
        $kebabCase = preg_replace('/-+/', '-', $kebabCase);
        return trim($kebabCase, '-');
    }
    
    /**
     * Convert a string to CamelCase
     */
    public static function toCamelCase(string $input): string
    {
        $input = trim($input, '"\'');
        $input = str_replace(['-', '_'], ' ', $input);
        return str_replace(' ', '', ucwords($input));
    }
}
