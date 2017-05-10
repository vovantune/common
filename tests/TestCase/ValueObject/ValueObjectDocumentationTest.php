<?php

namespace ArtSkills\Test\TestCase\ValueObject;

use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\Mock\MethodMocker;
use ArtSkills\ValueObject\ValueObjectDocumentation;

class ValueObjectDocumentationTest extends AppTestCase
{
	/** Генерилка доки */
	public function testMain() {
		ValueObjectDocumentation::build(__DIR__ . '/ValueObjectFixture.php');
		$jsDocFile = __DIR__ . '/ValueObjectFixture.js';
		self::assertEquals("/**
 * @typedef {Object} ArtSkills_Test_TestCase_ValueObject_ValueObjectFixture
 * @property {string} field1
 * @property field2
 * @property {ArtSkills_Lib_Strings} field3
 * @property {Cake_Utility_String} field4
 */
", file_get_contents($jsDocFile));
		unlink($jsDocFile);
	}

	/** namespace в файле */
	public function testGetFullNamespace() {
		self::assertEquals('ArtSkills\Test\TestCase\ValueObject', MethodMocker::callPrivate(ValueObjectDocumentation::class, '_getFullNamespace', [__FILE__]));
	}

	/** Имя класса из файла */
	public function testGetClassName() {
		self::assertEquals('ValueObjectFixture', MethodMocker::callPrivate(ValueObjectDocumentation::class, '_getClassName', [__DIR__ . '/ValueObjectFixture.php']));
	}

	/** Список использованных объектов */
	public function testGetUsesList() {
		self::assertEquals([
			'Strings' => 'ArtSkills\Lib\Strings',
			'ValueObject' => 'ArtSkills\ValueObject\ValueObject',
			'CakeString' => 'Cake\Utility\String',
		], MethodMocker::callPrivate(ValueObjectDocumentation::class, '_getUsesList', [__DIR__ . '/ValueObjectFixture.php']));
	}

	/** Преобразовываем имя из namespace в PSR0 */
	public function testConvertPsr4ToPsr0() {
		self::assertEquals('Test_Object', MethodMocker::callPrivate(ValueObjectDocumentation::class, '_convertPsr4ToPsr0', ['\Test\Object']));
		self::assertEquals('Test_ObjectSecond', MethodMocker::callPrivate(ValueObjectDocumentation::class, '_convertPsr4ToPsr0', ['App\Test\ObjectSecond']));
		self::assertEquals('Test_ObjectThird', MethodMocker::callPrivate(ValueObjectDocumentation::class, '_convertPsr4ToPsr0', ['Test\ObjectThird']));
	}
}