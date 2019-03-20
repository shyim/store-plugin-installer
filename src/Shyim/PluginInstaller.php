<?php

namespace Shyim;

use Shyim\Api\Client;

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
     * @var array
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
        $plugin = array_filter($this->licenses, function ($license) use ($name) {
            // Basic Plugins like SwagCore
            if (!isset($license['plugin'])) {
                return false;
            }

            return $license['plugin']['name'] === $name || $license['plugin']['code'] === $name;
        });

        if (empty($plugin)) {
            throw new \RuntimeException(sprintf('[Installer] Plugin with name "%s" is not available in your Account. Please buy the plugin first', $name));
        }

        $plugin = array_values($plugin)[0];

        // Fix plugin name
        $name = $plugin['plugin']['name'];

        $versions = array_column($plugin['plugin']['binaries'], 'version');

        $version = VersionSelector::getVersion($name, $version, $versions);

        if (!in_array($version, $versions)) {
            throw new \RuntimeException(sprintf('[Installer] Plugin with name "%s" doesnt have the version "%s", Available versions are %s', $name, $version, implode(', ', array_reverse($versions))));
        }

        if ($path = LocalCache::getPlugin($name, $version)) {
            ComposerPlugin::$io->write(sprintf('[Installer] Using plugin "%s" with version %s from cache', $name, $version), true);

            self::extractPlugin($path);

            return;
        }

        $binaryVersion = array_values(array_filter($plugin['plugin']['binaries'], function ($binary) use ($version) {
            return $binary['version'] === $version;
        }))[0];

        ComposerPlugin::$io->write(sprintf('[Installer] Downloading plugin "%s" with version %s', $name, $version), true);

        $this->movePlugin($this->client->downloadPlugin($binaryVersion), $name, $version);
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
        if (is_array($content)) {
            if (array_key_exists('success', $content)) {
                if (!$content['success']) {
                    throw new \InvalidArgumentException(sprintf('Could not download plugin %s in version %s maybe not a valid licence for this version', $name, $version));
                }
            }
        }

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
}
