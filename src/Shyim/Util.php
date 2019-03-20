<?php

namespace Shyim;

/**
 * Class Util
 */
class Util
{
    const REGEX = '/([\d]+\.[\d]+\.[\d]+(\-[a-zA-Z\d]{0,4})?)/';

    /**
     * @return string
     */
    public static function getShopwareVersion(): string
    {
        $version = self::getenv('SHOPWARE_VERSION', self::getComposerVersion());

        if (!$version) {
            throw new \RuntimeException(sprintf('Version %s is invalid', $version));
        }

        if (!preg_match(self::REGEX, $version, $match)) {
            throw new \RuntimeException(sprintf('Version %s is invalid', $version));
        }

        return $match[0];
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function getenv($name, $default = false)
    {
        $var = getenv($name);
        if (!$var) {
            $var = $default;
        }

        return $var;
    }

    /**
     * @return string
     */
    private static function getComposerVersion()
    {
        if (!class_exists(\PackageVersions\Versions)) {
            return null;
        }

        list($version, $sha) = explode('@', \PackageVersions\Versions::getVersion('shopware/shopware'));

        return $version;
    }
}
