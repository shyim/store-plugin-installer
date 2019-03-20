<?php

namespace Shyim\Struct\License;

class Binaries extends \Shyim\Struct\Struct
{
    /** @var int */
    public $id;

    /** @var string */
    public $filePath;

    /** @var string */
    public $version;

    /** @var CreationDate */
    public $creationDate;

    /** @var CompatibleSoftwareVersions */
    public $compatibleSoftwareVersions;

    /** @var bool */
    public $isLicenseCheckEnabled;

    /** @var Changelogs */
    public $changelogs;

    public static $mappedFields = [
        'creationDate' => 'Shyim\Struct\License\CreationDate',
        'compatibleSoftwareVersions' => 'Shyim\Struct\License\CompatibleSoftwareVersions',
        'changelogs' => 'Shyim\Struct\License\Changelogs',
    ];
}
