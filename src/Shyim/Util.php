<?php

namespace Shyim;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Util
 */
class Util
{
    const REGEX = '/([\d]+\.[\d]+\.[\d]+(\-[a-zA-Z\d]{0,4})?)/';

    /**
     * @var OutputInterface
     */
    public static $io;

    /**
     * @var bool
     */
    public static $silentFail;

    /**
     * @return string
     */
    public static function getShopwareVersion(): string
    {
        $version = self::getEnv('SHOPWARE_VERSION', self::getComposerVersion());

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
    public static function getEnv($name, $default = false)
    {
        $var = getenv($name);
        if (!$var) {
            $var = $default;
        }

        return $var;
    }

    /**
     * Handle exceptions and errors
     *
     * @param string $msg
     *
     * @throws \Throwable
     *
     * @return bool
     */
    public static function throwException(\Throwable $e)
    {
        if (self::$silentFail) {
            self::$io->write($e->getMessage(), true);
        } else {
            throw $e;
        }

        return false;
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
