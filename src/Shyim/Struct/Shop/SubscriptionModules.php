<?php

namespace Shyim\Struct\Shop;

class SubscriptionModules extends \Shyim\Struct\Struct
{
    /** @var int */
    public $id;

    /** @var Module */
    public $module;

    /** @var Status */
    public $status;

    /** @var string */
    public $expirationDate;

    /** @var string */
    public $creationDate;

    /** @var bool */
    public $monthlyPayment;

    /** @var int */
    public $durationInMonths;

    /** @var DurationOptions */
    public $durationOptions;

    /** @var bool */
    public $automaticExtension;

    public static $mappedFields = [
        'module' => 'Shyim\Struct\Shop\Module',
        'status' => 'Shyim\Struct\Shop\Status',
        'durationOptions' => 'Shyim\Struct\Shop\DurationOptions',
    ];
}
