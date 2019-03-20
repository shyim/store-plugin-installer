<?php
namespace Shyim\Struct\Plugin;

class Plugin extends \Shyim\Struct\Struct
{
	/** @var integer */
	public $id;

	/** @var string */
	public $name;

	/** @var string */
	public $code;

	/** @var boolean */
	public $useContactForm;

	/** @var LastChange */
	public $lastChange;

	/** @var boolean */
	public $support;

	/** @var boolean */
	public $supportOnlyCommercial;

	/** @var string */
	public $iconPath;

	/** @var string */
	public $examplePageUrl;

	/** @var string */
	public $moduleKey;

	/** @var LastChange */
	public $creationDate;

	/** @var string */
	public $statusComment;

	/** @var boolean */
	public $responsive;

	/** @var boolean */
	public $automaticBugfixVersionCompatibility;

	/** @var boolean */
	public $hiddenInStore;

	/** @var Producer */
	public $producer;

	/** @var PriceModels */
	public $priceModels;

	/** @var Pictures */
	public $pictures;

	/** @var Comments */
	public $comments;

	/** @var integer */
	public $ratingAverage;

	/** @var string */
	public $label;

	/** @var string */
	public $description;

	/** @var string */
	public $installationManual;

	/** @var string */
	public $version;

	/** @var Changelog */
	public $changelog;

	/** @var Addons */
	public $addons;

	/** @var string */
	public $link;

	/** @var boolean */
	public $redirectToStore;

	/** @var NULL */
	public $lowestPriceValue;

	public static $mappedFields = [
		'lastChange' => 'Shyim\Struct\Plugin\LastChange',
		'creationDate' => 'Shyim\Struct\Plugin\LastChange',
		'producer' => 'Shyim\Struct\Plugin\Producer',
		'priceModels' => 'Shyim\Struct\Plugin\PriceModels',
		'pictures' => 'Shyim\Struct\Plugin\Pictures',
		'comments' => 'Shyim\Struct\Plugin\Comments',
		'changelog' => 'Shyim\Struct\Plugin\Changelog',
		'addons' => 'Shyim\Struct\Plugin\Addons',
	];
}
