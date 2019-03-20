<?php
namespace Shyim\Struct\Plugin;

class Changelog extends \Shyim\Struct\Struct
{
	/** @var string */
	public $version;

	/** @var string */
	public $text;

	/** @var LastChange */
	public $creationDate;

	public static $mappedFields = ['creationDate' => 'Shyim\Struct\Plugin\LastChange'];
}
