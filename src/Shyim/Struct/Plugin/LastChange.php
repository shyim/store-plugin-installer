<?php
namespace Shyim\Struct\Plugin;

class LastChange extends \Shyim\Struct\Struct
{
	/** @var string */
	public $date;

	/** @var integer */
	public $timezone_type;

	/** @var string */
	public $timezone;

	public static $mappedFields = [];
}
