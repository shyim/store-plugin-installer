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
     * @var array
     */
    private static $shop;

    /**
     * @var IOInterface
     */
    private static $io;

    /**
     * @var array
     */
    private static $extra;

    /**
     * @var array
     */
    private static $licenses;

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

        self::$extra = $extra;

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
        $plugin = array_filter(self::$licenses, function ($license) use($name) {
            // Basic Plugins like SwagCore
            if (!isset($license['plugin'])) {
                return false;
            }

            return $license['plugin']['name'] === $name;
        });

        if (empty($plugin)) {
            throw new \RuntimeException(sprintf('Plugin with name "%s" is not available in your Account. Please buy the plugin first', $name));
        }

        $plugin = array_values($plugin)[0];

        $versions = array_column($plugin['plugin']['binaries'], 'version');

        if (!in_array($version, $versions)) {
            throw new \RuntimeException(sprintf('Plugin with name "%s" doesnt have the version "%s"', $name, $version));
        }

        if ($path = LocalCache::getPlugin($name, $version)) {
            self::$io->write(sprintf('[Installer] Using plugin "%s" with version %s from cache', $name, $version), true);

            self::extractPlugin($path);
            return;
        }

        $binaryVersion = array_values(array_filter($plugin['plugin']['binaries'], function ($binary) use($version) {
            return $binary['version'] === $version;
        }))[0];

        self::$io->write(sprintf('[Installer] Downloading plugin "%s" with version %s', $name, $version), true);

        self::downloadAndMovePlugin(self::BASE_URL . $binaryVersion['filePath'] . '?token=' . self::$token . '&shopId=' . self::$shop['id'], $name, $version);
    }

    /**
     * @param string $path
     * @param string $method
     * @param array $params
     * @return array
     */
    private static function apiRequest($path, $method, array $params = [])
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
                'X-Shopware-Token: ' . self::$token,
                'Useragent: Composer (Shopware-Store-Installer)',
            ]);
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
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
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
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
     * @param string $name
     * @return string
     */
    private static function getExtractLocation($name)
    {
        $paths = self::$extra['installer-paths'];

        foreach ($paths as $folder => $types) {
            $possibleValues = ['shopware-backend-plugin', 'shopware-frontend-plugin', 'shopware-core-plugin'];
            $possibleTypes = ['Frontend', 'Core', 'Core'];

            $types[0] = str_replace('type:', '', $types[0]);

            if (in_array($name, $possibleTypes)) {
                if (in_array($types[0], $possibleValues)) {
                    return dirname(getcwd() . '/' . str_replace('{$name}/', '', $folder));
                }
            } elseif($types[0] === 'shopware-plugin') {
                return dirname(getcwd() . '/' . str_replace('{$name}/', '', $folder));
            }
        }

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

        if (empty($user) || empty($password)) {
            self::$io->writeError('[Installer] The enviroment variable $ACCOUNT_USER and $ACCOUNT_PASSWORD are required!');
            return;
        } else {
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

            $partnerAccount = self::apiRequest('/partners/'.$response['userId'], 'GET');

            if ($partnerAccount && !empty($partnerAccount['partnerId'])) {
                echo '[Installer] Account is partner account' . PHP_EOL;

                $clientshops = self::apiRequest('/partners/'.$response['userId'].'/clientshops', 'GET');
            } else {
                $clientshops = [];
            }

            $shops = self::apiRequest('/shops', 'GET', [
                'userId' => $response['userId']
            ]);

            $domains = array_merge(array_column($clientshops, 'domain'), array_column($shops, 'domain'));

            $domain = parse_url(self::getenv('SHOP_URL'), PHP_URL_HOST);

            if (!in_array($domain, $domains)) {
                $wildCardDomains = array_filter($domains, function($domain) {
                    return substr($domain, 0, 1) == '.';
                });

                if (!empty($wildCardDomains)) {
                    foreach ($wildCardDomains as $wildCardDomain) {
                        if (strpos($domain, $wildCardDomain) !== false) {
                            $domain = $wildCardDomain;
                            break;
                        }
                    }

                    if (substr($domain, 0, 1) != '.') {
                        throw new \RuntimeException(sprintf('Shop with given domain "%s" does not exist!', $domain));
                    }
                } else {
                    throw new \RuntimeException(sprintf('Shop with given domain "%s" does not exist!', $domain));
                }
            }

            self::$io->write(sprintf('[Installer] Found shop with domain "%s" in account', $domain), true);

            $shops = array_merge($shops, $clientshops);

            self::$shop = array_filter($shops, function($shop) use($domain) {
                return $shop['domain'] === $domain;
            });

            self::$shop = array_values(self::$shop)[0];

            self::$licenses = self::apiRequest('/licenses', 'GET', [
                'partnerId' => $response['userId'],
                'shopId' => self::$shop['id']
            ]);
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
