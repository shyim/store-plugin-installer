<?php
namespace Shyim\Struct\Plugin;

class Comments extends \Shyim\Struct\Struct
{
	/** @var string */
	public $authorName;

	/** @var string */
	public $text;

	/** @var string */
	public $headline;

	/** @var LastChange */
	public $creationDate;

	/** @var integer */
	public $rating;

	public static $mappedFields = ['creationDate' => 'Shyim\Struct\Plugin\LastChange'];
}
