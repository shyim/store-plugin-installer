<?php

namespace Shyim;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Dotenv\Dotenv;

/**
 * Class PluginInstaller
 * @package Shopware
 */
class PluginInstaller implements PluginInterface, EventSubscriberInterface
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
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'installPlugins',
            ScriptEvents::POST_UPDATE_CMD  => 'installPlugins',
        ];
    }

    /**
     * We dont need activate
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io) {}

    /**
     * @param Event $e
     * @throws \Exception
     */
    public static function installPlugins(Event $e)
    {
        self::$io = $e->getIO();
        LocalCache::init();
        self::readPlugins($e);

        foreach (self::$plugins as $plugin => $version) {
            self::downloadPlugin($plugin, $version);
        }
    }


    /**
     * Read plugins from the plugins.ini from root
     */
    private static function readPlugins(Event $e)
    {
        $envFile = getcwd() . '/.env';

        if (file_exists($envFile)) {
            (new Dotenv(getcwd()))->load();
        }

        $extra = $e->getComposer()->getPackage()->getExtra();

        if (isset($extra['plugins'])) {
            $env = self::getenv('SHOPWARE_ENV', 'production');

            if (!isset($extra['plugins'][$env])) {
                self::$io->write(sprintf('Cannot find plugins for environment "%s"', $env), true);
                return;
            }

            self::$plugins = $extra['plugins'][$env];
        } else {
            self::$io->write('[Installer] Cannot find plugins in composer.json extra', true);
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
     * @param string $name
     * @param string $version
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

    /**
     * @param string $zipFile
     */
    private static function extractPlugin($zipFile)
    {
        $zip = new \ZipArchive();
        $zip->open($zipFile);

        $location = self::getExtractLocation(basename($zip->statIndex(0)['name']));

        $zip->extractTo($location);

        $zip->close();
    }

    /**
     * @todo: Read location from composer.json
     * @param string $name
     * @return string
     */
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

    /**
     * Login into the shopware account
     */
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
     * @param string $name
     * @param mixed $default
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