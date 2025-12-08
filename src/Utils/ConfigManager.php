<?php

namespace Noctalys\Cli\Utils;

/**
 * Utility class to manage project configuration files
 */
class ConfigManager
{
    /**
     * Load configuration from file
     * 
     * @param string $filePath Path to the configuration file
     * @return array|null Configuration data or null if loading fails
     */
    public static function load(string $filePath): ?array
    {
        if (!file_exists($filePath)) {
            return null;
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }
        
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $data;
    }
    
    /**
     * Save configuration to file
     * 
     * @param string $filePath Path to the configuration file
     * @param array $config Configuration data to save
     * @return bool True if save was successful
     */
    public static function save(string $filePath, array $config): bool
    {
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }
        
        return file_put_contents($filePath, $json . "\n") !== false;
    }
    
    /**
     * Get a value from configuration using dot notation
     * 
     * @param array $config Configuration data
     * @param string $path Path using dot notation (e.g. "app.name")
     * @param mixed $default Default value if path not found
     * @return mixed Value at path or default if not found
     */
    public static function get(array $config, string $path, $default = null)
    {
        $keys = explode('.', $path);
        $current = $config;
        
        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return $default;
            }
            $current = $current[$key];
        }
        
        return $current;
    }
    
    /**
     * Set a value in configuration using dot notation
     * 
     * @param array &$config Configuration data (passed by reference)
     * @param string $path Path using dot notation (e.g. "app.name")
     * @param mixed $value Value to set
     * @return bool True if value was set
     */
    public static function set(array &$config, string $path, $value): bool
    {
        $keys = explode('.', $path);
        $lastKey = array_pop($keys);
        $current = &$config;
        
        foreach ($keys as $key) {
            if (!is_array($current)) {
                $current = [];
            }
            
            if (!array_key_exists($key, $current)) {
                $current[$key] = [];
            }
            
            $current = &$current[$key];
        }
        
        $current[$lastKey] = $value;
        return true;
    }
    
    /**
     * Check if a path exists in configuration using dot notation
     * 
     * @param array $config Configuration data
     * @param string $path Path using dot notation (e.g. "app.name")
     * @return bool True if path exists
     */
    public static function has(array $config, string $path): bool
    {
        $keys = explode('.', $path);
        $current = $config;
        
        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return false;
            }
            $current = $current[$key];
        }
        
        return true;
    }
}
