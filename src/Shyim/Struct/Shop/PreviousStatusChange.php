<?php

namespace Shyim\Struct\Shop;

class PreviousStatusChange extends \Shyim\Struct\Struct
{
    /** @var int */
    public $id;

    /** @var int */
    public $shopId;

    /** @var string */
    public $statusCreationDate;

    /** @var null */
    public $previousStatusChange;

    /** @var ShopDomainVerificationStatus */
    public $shopDomainVerificationStatus;

    public static $mappedFields = ['shopDomainVerificationStatus' => 'Shyim\Struct\Shop\ShopDomainVerificationStatus'];
}
