<?php

namespace Shyim;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Dotenv\Dotenv;


/**
 * Class PluginInstaller
 * @package Shopware
 */
class PluginInstaller
{
    const BASE_URL = 'https://api.shopware.com';

    /**
     * @var array
     */
    private static $plugins = [];

    /**
     * @var string
     */
    private static $token;

    /**
     * @var IOInterface
     */
    private static $io;

    /**
     * @param Event $e
     * @throws \Exception
     */
    public static function installPlugins(Event $e)
    {
        self::$io = $e->getIO();
        LocalCache::init();
        self::readPlugins();

        foreach (self::$plugins as $plugin => $version) {
            self::downloadPlugin($plugin, $version);
        }
    }


    private static function readPlugins()
    {
        $envFile = getcwd() . '/.env';

        if (file_exists($envFile)) {
            (new Dotenv(getcwd()))->load();
        }

        $file = getcwd() . '/plugins.ini';
        if (file_exists($file)) {
            self::$plugins = parse_ini_file($file, true);
            $env = self::getenv('SHOPWARE_ENV', 'production');

            if (!isset(self::$plugins[$env])) {
                throw new \RuntimeException(sprintf('Cannot find plugins for environment "%s"', $env));
            }

            self::$plugins = self::$plugins[$env];
        } else {
            self::$io->write('[Installer] Cannot found a plugins.ini', true);
        }


        self::loginAccount();
    }

    /**
     * Starts a download from api
     *
     * @param string $name
     * @param string $version
     * @throws \Exception
     */
    private static function downloadPlugin($name, $version)
    {
        $params = [
            'locale' => 'en_GB',
            'shopwareVersion' => self::getenv('SHOPWARE_VERSION', '5.4.5'),
            'technicalNames' => $name
        ];

        self::$io->write(sprintf('[Installer] Downloading plugin "%s"', $name), true);

        $response = self::apiRequest('/pluginStore/pluginsByName', 'GET', $params);

        self::validateVersion($name, $version, $response);

        if ($path = LocalCache::getPlugin($name, $version)) {
            self::$io->write(sprintf('[Installer] Using plugin "%s" with version %s from cache', $name, $version), true);

            self::extractPlugin($path);
            return;
        }

        $params = [
            'domain' => parse_url(self::getenv('SHOP_URL'), PHP_URL_HOST),
            'technicalName' => $name,
            'shopwareVersion' => self::getenv('SHOPWARE_VERSION', '5.4.5')
        ];

        $response = self::apiRequest('/pluginFiles/' . $name . '/data', 'GET', $params);

        if (!isset($response['location'])) {
            throw new \Exception(sprintf('Plugin Download for "%s" failed with code "%s"', $name, $response['code']));
        }

        self::$io->write(sprintf('[Installer] Downloading plugin "%s" with version %s', $name, $response['binaryVersion']), true);

        self::downloadAndMovePlugin($response['location'], $name, $response['binaryVersion']);
    }

    /**
     * @param string $path
     * @param string $method
     * @param array $params
     * @return array
     */
    private static function apiRequest($path, $method, array $params)
    {
        if ($method === 'GET') {
            $path .= '?' . http_build_query($params);
        }

        $ch = curl_init(self::BASE_URL . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        if (!empty(self::$token)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-Shopware-Token: ' . self::$token
            ]);
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * @param string $name
     * @param string $version
     * @param array $response
     * @throws \Exception
     */
    private static function validateVersion($name, $version, $response)
    {
        $versions = array_column($response[0]['changelog'], 'version');

        if (!in_array($version, $versions)) {
            throw new \Exception(sprintf('Plugin "%s" does not have the version %s', $name, $version));
        }
    }

    /**
     * @param string $url
     */
    private static function downloadAndMovePlugin($url, $name, $version)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        curl_close($ch);

        $file = LocalCache::getCachePath($name, $version);

        file_put_contents($file, $content);

        self::extractPlugin($file);
    }

    private static function extractPlugin($zipFile)
    {
        $zip = new \ZipArchive();
        $zip->open($zipFile);

        $location = self::getExtractLocation(basename($zip->statIndex(0)['name']));

        $zip->extractTo($location);

        $zip->close();
    }

    private static function getExtractLocation($name)
    {
        switch ($name) {
            case 'Frontend':
            case 'Core':
            case 'Backend':
                return getcwd() . '/Plugins/Community/';
            default:
                return getcwd() . '/custom/plugins/';
        }
    }

    private static function loginAccount()
    {
        $user = self::getenv('ACCOUNT_USER');
        $password = self::getenv('ACCOUNT_PASSWORD');

        if (!empty($user) && !empty($password)) {
            echo '[Installer] Using $ACCOUNT_USER and $ACCOUNT_PASSWORD to login into the account' . PHP_EOL;

            $response = self::apiRequest('/accesstokens', 'POST', [
                'shopwareId' => $user,
                'password' => $password
            ]);

            if (isset($response['success']) && $response['success'] === false) {
                throw new \RuntimeException(sprintf('Login to Account failed with code %s', $response['code']));
            }

            echo '[Installer] Successfully loggedin in the account' . PHP_EOL;

            self::$token = $response['token'];
        }
    }

    /**
     * @param $name
     * @param $default
     * @return mixed
     */
    private static function getenv($name, $default = false)
    {
        $var = getenv($name);
        if (!$var) {
            $var = $default;
        }
        
        return $var;
    }
}