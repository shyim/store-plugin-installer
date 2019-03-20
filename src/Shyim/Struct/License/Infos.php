<?php

namespace Shyim\Struct\License;

class Infos extends \Shyim\Struct\Struct
{
    /** @var int */
    public $id;

    /** @var Locale */
    public $locale;

    /** @var string */
    public $name;

    public static $mappedFields = ['locale' => 'Shyim\Struct\License\Locale'];
}
