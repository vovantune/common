<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 12.05.17
 * Time: 17:05
 */

namespace ArtSkills\Test\TestCase\ValueObject;

class ValueObjectFixtureSecond extends ValueObjectFixture
{
	/**
	 * @var $this
	 */
	public $thisProperty = null;

	/**
	 * @var int|string
	 */
	public $multiplyProperty;

	/**
	 * @var int[]
	 */
	public $intArray;

	/**
	 * @var array
	 */
	public $arrayArray;

	/**
	 * @var mixed
	 */
	public $mixedProperty;
}