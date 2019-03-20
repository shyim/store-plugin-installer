<?php

namespace Shyim\Struct\License;

class CreationDate extends \Shyim\Struct\Struct
{
    /** @var string */
    public $date;

    /** @var int */
    public $timezone_type;

    /** @var string */
    public $timezone;

    public static $mappedFields = [];
}
