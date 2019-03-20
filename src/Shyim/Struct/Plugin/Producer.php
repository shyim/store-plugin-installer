<?php
namespace Shyim\Struct\Plugin;

class Producer extends \Shyim\Struct\Struct
{
	/** @var integer */
	public $id;

	/** @var string */
	public $prefix;

	/** @var string */
	public $name;

	/** @var string */
	public $website;

	/** @var boolean */
	public $fixed;

	/** @var string */
	public $iconPath;

	public static $mappedFields = [];
}
