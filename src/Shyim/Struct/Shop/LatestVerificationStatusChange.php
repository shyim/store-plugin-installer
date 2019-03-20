<?php

namespace Shyim\Struct\Shop;

class LatestVerificationStatusChange extends \Shyim\Struct\Struct
{
    /** @var int */
    public $id;

    /** @var int */
    public $shopId;

    /** @var string */
    public $statusCreationDate;

    /** @var PreviousStatusChange */
    public $previousStatusChange;

    /** @var ShopDomainVerificationStatus */
    public $shopDomainVerificationStatus;

    public static $mappedFields = [
        'previousStatusChange' => 'Shyim\Struct\Shop\PreviousStatusChange',
        'shopDomainVerificationStatus' => 'Shyim\Struct\Shop\ShopDomainVerificationStatus',
    ];
}
