<?php

namespace Shyim;

/**
 * Class LocalCache
 * @package Shyim
 */
class LocalCache
{
    public static $path;

    public static function init($path = null)
    {
        if ($path === null) {
            self::$path = getenv('HOME') . '/.shopware-plugins/';
        } else {
            self::$path = rtrim($path, '/') . '/.shopware-plugins/';
        }

        if (!file_exists(self::$path)) {
            if (!mkdir($concurrentDirectory = self::$path) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }
    }

    /**
     * @param $name
     * @param $version
     * @return null|string
     */
    public static function getPlugin($name, $version)
    {
        $path = self::$path . self::buildPluginZipName($name, $version);
        if (file_exists($path)) {
            return $path;
        }

        return null;
    }

    /**
     * @param string $name
     * @param string $version
     * @return string
     */
    public static function getCachePath($name, $version)
    {
        return self::$path . self::buildPluginZipName($name, $version);
    }

    /**
     * @param string $name
     * @param string $version
     * @return string
     */
    private static function buildPluginZipName($name, $version)
    {
        return sprintf('%s-%s.zip', $name, $version);
    }
}