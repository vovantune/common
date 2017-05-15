<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 05.05.17
 * Time: 16:30
 */

namespace TestApp\Controller;

use ArtSkills\Lib\ValueObject;

class TestValueObject extends ValueObject
{
	public $testProperty = 'testData';
}