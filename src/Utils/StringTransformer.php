<?php

namespace Goramax\NoctalysCli\Utils;

class StringTransformer
{
    /**
     * Convert a string to kebab-case (lowercase with hyphens)
     * 
     * @param string $input The input string
     * @return string The kebab-case string
     */
    public static function toKebabCase(string $input): string
    {
        // Remove quotes if present
        $input = trim($input, '"\'');
        
        // Replace spaces and underscores with hyphens
        $kebabCase = preg_replace('/[\s_]+/', '-', $input);
        
        // Convert to lowercase and remove any non-alphanumeric characters (except hyphens)
        $kebabCase = preg_replace('/[^a-z0-9-]/', '', strtolower($kebabCase));
        
        // Remove multiple consecutive hyphens
        $kebabCase = preg_replace('/-+/', '-', $kebabCase);
        
        // Remove leading and trailing hyphens
        return trim($kebabCase, '-');
    }
    
    /**
     * Convert a string to CamelCase (no spaces, capitalized words)
     * 
     * @param string $input The input string
     * @return string The CamelCase string
     */
    public static function toCamelCase(string $input): string
    {
        // Remove quotes if present
        $input = trim($input, '"\'');
        
        // Replace hyphens and underscores with spaces
        $input = str_replace(['-', '_'], ' ', $input);
        
        // Capitalize each word and remove spaces
        return str_replace(' ', '', ucwords($input));
    }
}
