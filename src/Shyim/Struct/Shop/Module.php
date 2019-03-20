<?php

namespace Shyim\Struct\Shop;

class Module extends \Shyim\Struct\Struct
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $description;

    /** @var int */
    public $price;

    /** @var int */
    public $priceMonthlyPayment;

    /** @var int */
    public $price24;

    /** @var int */
    public $price24MonthlyPayment;

    /** @var int */
    public $upgradeOrder;

    /** @var int */
    public $durationInMonths;

    /** @var string */
    public $bookingKey;

    public static $mappedFields = [];
}
