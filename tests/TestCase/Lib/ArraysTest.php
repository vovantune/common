<?php
namespace App\Test\TestCase\Lib;

use ArtSkills\Lib\Arrays;
use ArtSkills\TestSuite\AppTestCase;

class ArraysTest extends AppTestCase
{

	/** фильтр части ключей */
	public function testFilterKeys() {
		$array = [
			'a' => 1,
			'b' => 2,
			'c' => 3,
			'd' => 4,
		];
		$expectedResult = [
			'a' => 1,
			'c' => 3,
		];
		self::assertEquals($expectedResult, Arrays::filterKeys($array, ['a', 'c']));
	}

	/** значения в ключи */
	public function testKeysFromValues() {
		$values = ['a', 'b', 'c'];
		$expectedResult = [
			'a' => 'a',
			'b' => 'b',
			'c' => 'c',
		];
		self::assertEquals($expectedResult, Arrays::keysFromValues($values));
	}

	/** обработка массива строк */
	public function testTrim() {
		$strings = [
			'    asd   asd asd    ',
			" \n\t",
		];
		$expectedResult = [
			'asd   asd asd',
			'',
		];
		self::assertEquals($expectedResult, Arrays::trim($strings));
	}

}
