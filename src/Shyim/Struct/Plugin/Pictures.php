<?php
namespace Shyim\Struct\Plugin;

class Pictures extends \Shyim\Struct\Struct
{
	/** @var string */
	public $remoteLink;

	/** @var boolean */
	public $preview;

	/** @var integer */
	public $priority;

	/** @var integer */
	public $id;

	public static $mappedFields = [];
}
