<?php

namespace Shyim\Struct\Shop;

class ShopDomainVerificationStatus extends \Shyim\Struct\Struct
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $description;

    public static $mappedFields = [];
}
