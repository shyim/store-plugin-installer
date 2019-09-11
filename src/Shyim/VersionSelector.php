<?php

namespace Shyim;

use Composer\DependencyResolver\Pool;
use Composer\Package\Package;
use Composer\Repository\InstalledArrayRepository;

class VersionSelector
{
    /**
     * @param string $name
     * @param string $version
     * @param array  $availableVersions
     *
     * @return string
     */
    public static function getVersion($name, $version, array $availableVersions)
    {
        try {
            $pool = new Pool();

            $packages = array_map(function ($version) use ($name) {
                return new Package($name, $version, $version);
            },
                $availableVersions);

            $pool->addRepository(new InstalledArrayRepository($packages));
            $selector = new \Composer\Package\Version\VersionSelector($pool);

            $constraintVersion = $selector->findBestCandidate($name, $version);

            return $constraintVersion ? $constraintVersion->getVersion() : $version;
        }catch(\Exception $e){
            return $version;
        }
    }
}
