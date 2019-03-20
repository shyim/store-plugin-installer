<?php

namespace Shyim\Struct\License;

class License extends \Shyim\Struct\Struct
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $description;

    /** @var Type */
    public $type;

    /** @var string */
    public $shop;

    /** @var int */
    public $shopId;

    /** @var Type */
    public $status;

    /** @var bool */
    public $subscription;

    /** @var bool */
    public $isExpired;

    /** @var string */
    public $licenseKey;

    /** @var PriceModel */
    public $priceModel;

    /** @var bool */
    public $payed;

    /** @var bool */
    public $rentIsActive;

    /** @var string */
    public $domain;

    /** @var int */
    public $shopwareMajorVersion;

    /** @var int */
    public $quantity;

    /** @var int */
    public $pluginId;

    /** @var string */
    public $creationDate;

    /** @var string */
    public $expirationDate;

    /** @var Plugin */
    public $plugin;

    public static $mappedFields = [
        'type' => 'Shyim\Struct\License\Type',
        'status' => 'Shyim\Struct\License\Type',
        'priceModel' => 'Shyim\Struct\License\PriceModel',
        'plugin' => 'Shyim\Struct\License\Plugin',
    ];
}
