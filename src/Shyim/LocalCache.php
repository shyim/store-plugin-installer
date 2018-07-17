<?php

namespace Shyim;


class LocalCache
{
    public static $path;

    public static function init()
    {
        self::$path = getenv('HOME') . '/.shopware-plugins/';

        if (!file_exists(self::$path)) {
            mkdir(self::$path);
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
     * @param static $version
     * @return string
     */
    public static function getCachePath($name, $version)
    {
        return self::$path . self::buildPluginZipName($name, $version);
    }

    /**
     * @param string $name
     * @param string $version
     */
    private static function buildPluginZipName($name, $version)
    {
        return sprintf('%s-%s.zip', $name, $version);
    }
}