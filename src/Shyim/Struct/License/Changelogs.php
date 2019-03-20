<?php

namespace Shyim\Struct\License;

class Changelogs extends \Shyim\Struct\Struct
{
    /** @var int */
    public $id;

    /** @var Locale */
    public $locale;

    /** @var string */
    public $text;

    public static $mappedFields = ['locale' => 'Shyim\Struct\License\Locale'];
}
