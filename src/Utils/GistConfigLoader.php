<?php

namespace Goramax\NoctalysCli\Utils;

class GistConfigLoader
{
    private static $config = null;
    private static $gistUrl = 'https://gist.githubusercontent.com/Goramax/b36781048ba63bf584b9eae9627b9530/raw';

    public static function getConfig()
    {
        if (self::$config !== null) {
            return self::$config;
        }
        $json = @file_get_contents(self::$gistUrl);
        if ($json !== false) {
            $data = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                self::$config = $data;
                return self::$config;
            }
        }
        self::$config = [];
        return self::$config;
    }

    public static function getEngines()
    {
        $config = self::getConfig();
        if (isset($config['engines']) && is_array($config['engines'])) {
            return $config['engines'];
        }
        return [];
    }

    public static function getCss()
    {
        $config = self::getConfig();
        if (isset($config['css']) && is_array($config['css'])) {
            return $config['css'];
        }
        return [];
    }
}
