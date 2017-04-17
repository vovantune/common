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

	/**
	 * смена ключей массива
	 */
	public function testRemap() {
		$array = [
			'before_1' => 'asd',
			'before_2' => 'qwe',
			'before_4' => 'zxc',
		];
		$map = [
			'before_1' => 'after1',
			'before_2' => 'after2',
			'before_3' => 'after3',
		];
		$expectedWithNull = [
			'after1' => 'asd',
			'after2' => 'qwe',
			'after3' => null,
		];
		$expectedStrict = [
			'after1' => 'asd',
			'after2' => 'qwe',
		];
		self::assertEquals($expectedWithNull, Arrays::remap($array, $map, true));
		self::assertEquals($expectedStrict, Arrays::remap($array, $map, false));
	}

	/** получение */
	public function testGet() {
		$default = 'default';
		$hasKey = 'hasKey';
		$hasNotKey = 'hasNotKey';
		$value = 'value';

		$array = [$hasKey => $value];
		self::assertEquals($value, Arrays::get($array, $hasKey));
		self::assertEquals($default, Arrays::get($array, $hasNotKey, $default));
		self::assertEquals(null, Arrays::get($array, $hasNotKey));
		self::assertEquals(null, Arrays::get(null, $hasNotKey));
	}

}
