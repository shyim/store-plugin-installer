<?php

namespace Shyim\Struct\License;

class Type extends \Shyim\Struct\Struct
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $description;

    public static $mappedFields = [];
}
