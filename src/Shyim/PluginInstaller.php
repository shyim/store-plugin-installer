<?php

namespace Shyim;

use Shyim\Api\Client;
use Shyim\Struct\License\Binaries;
use Shyim\Struct\License\License;
use Shyim\Struct\License\Plugin;

class PluginInstaller
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $extra;

    /**
     * @var License[]
     */
    private $licenses;

    public function __construct(Client $client, array $extra)
    {
        $this->client = $client;
        $this->extra = $extra;
        $this->licenses = $client->getLicenses();
    }

    public function installPlugin(string $name, string $version)
    {
        $license = array_filter($this->licenses, function (License $license) use ($name) {
            // Basic Plugins like SwagCore
            if (!isset($license->plugin)) {
                return false;
            }

            return $license->plugin->name === $name || $license->plugin->code === $name;
        });

        if (empty($license)) {
            $this->checkExistenceOfPlugin($name);
        }

        /** @var License $license */
        $license = array_values($license)[0];

        // Fix plugin name
        $name = $license->plugin->name;

        $versions = array_map(function (Binaries $binary) {
            return $binary->version;
        }, $license->plugin->binaries);

        $version = VersionSelector::getVersion($name, $version, $versions);

        if (!in_array($version, $versions)) {
            throw new \RuntimeException(sprintf('[Installer] Plugin with name "%s" doesnt have the version "%s", Available versions are %s', $name, $version, implode(', ', array_reverse($versions))));
        }

        if ($path = LocalCache::getPlugin($name, $version)) {
            ComposerPlugin::$io->write(sprintf('[Installer] Using plugin "%s" with version %s from cache', $name, $version), true);

            self::extractPlugin($path);

            return;
        }

        $binaryVersion = array_values(array_filter($license->plugin->binaries, function (Binaries $binary) use ($version) {
            return $binary->version === $version;
        }))[0];

        ComposerPlugin::$io->write(sprintf('[Installer] Downloading plugin "%s" with version %s', $name, $version), true);

        $this->movePlugin($this->client->downloadPlugin($binaryVersion, $name, $version), $name, $version);
    }

    /**
     * @param string|array $content
     * @param string       $name
     * @param string       $version
     *
     * @throws \Exception
     */
    private function movePlugin($content, $name, $version)
    {
        $file = LocalCache::getCachePath($name, $version);

        file_put_contents($file, $content);

        $this->extractPlugin($file);
    }

    private function extractPlugin(string $zipFile)
    {
        try {
            $zip = new \ZipArchive();
            $zip->open($zipFile);
            $folderpath = str_replace('\\', '/', $zip->statIndex(0)['name']);
            $pos = strpos($folderpath, '/');
            $path = substr($folderpath, 0, $pos);
            $location = $this->getExtractLocation($path);
            $zip->extractTo($location);
            $zip->close();
        } catch (\Exception $e) {
            LocalCache::cleanByPath($zipFile);
            Util::throwException($e);
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getExtractLocation(string $name)
    {
        if (isset($this->extra['installer-paths'])) {
            foreach ($this->extra['installer-paths'] as $folder => $types) {
                $possibleValues = ['shopware-backend-plugin', 'shopware-frontend-plugin', 'shopware-core-plugin'];
                $possibleTypes = ['Frontend', 'Core', 'Backend'];

                $types[0] = str_replace('type:', '', $types[0]);

                if (in_array($name, $possibleTypes)) {
                    if (in_array($types[0], $possibleValues)) {
                        return dirname(getcwd() . '/' . str_replace('{$name}/', '', $folder));
                    }
                } elseif ($types[0] === 'shopware-plugin') {
                    return dirname(getcwd() . '/' . str_replace('{$name}/', '', $folder));
                }
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

    private function checkExistenceOfPlugin(string $name)
    {
        $plugins = $this->client->searchPlugin($name);
        $found = false;

        foreach ($plugins as $plugin) {
            if ($plugin->code === $name || $plugin->name === $name) {
                $found = true;
            }
        }

        if ($found || empty($plugins)) {
            throw new \RuntimeException(sprintf('[Installer] Plugin with name "%s" is not available in your Account. Please buy the plugin first', $name));
        }

        $names = array_map(function(\Shyim\Struct\Plugin\Plugin $plugin) {
            return $plugin->name;
        }, $plugins);

        throw new \RuntimeException(sprintf('[Installer] Could not find plugin by name "%s". Did you mean some of %s', $name, implode(', ', $names)));
    }
}
