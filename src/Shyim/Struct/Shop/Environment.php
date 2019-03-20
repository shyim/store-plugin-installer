<?php

namespace Shyim\Struct\Shop;

class Environment extends \Shyim\Struct\Struct
{
    /** @var string */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $description;

    public static $mappedFields = [];
}
