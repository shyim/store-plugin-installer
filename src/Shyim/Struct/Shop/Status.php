<?php

namespace Shyim\Struct\Shop;

class Status extends \Shyim\Struct\Struct
{
    /** @var null */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $description;

    public static $mappedFields = [];
}
