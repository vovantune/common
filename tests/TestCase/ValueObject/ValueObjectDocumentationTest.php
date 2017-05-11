<?php

namespace ArtSkills\Test\TestCase\ValueObject;

use ArtSkills\Lib\Strings;
use ArtSkills\ValueObject\ValueObject;
use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\Mock\MethodMocker;
use ArtSkills\ValueObject\ValueObjectDocumentation;
use Cake\Utility\String;

class ValueObjectDocumentationTest extends AppTestCase
{
	/** Генерилка доки */
	public function testMain() {
		ValueObjectDocumentation::build(__DIR__ . '/ValueObjectFixture.php', __DIR__);
		$jsDocFile = __DIR__ . '/ArtSkills_Test_TestCase_ValueObject_ValueObjectFixture.js';
		self::assertFileExists($jsDocFile);
		self::assertEquals("// Auto generated file, to change structure edit ArtSkills\Test\TestCase\ValueObject\ValueObjectFixture php class
/**
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
		self::assertEquals(namespaceSplit(ValueObjectFixture::class)[0], MethodMocker::callPrivate(ValueObjectDocumentation::class, '_getFullNamespace', [__FILE__]));
	}

	/** Имя класса из файла */
	public function testGetClassName() {
		self::assertEquals(namespaceSplit(ValueObjectFixture::class)[1], MethodMocker::callPrivate(ValueObjectDocumentation::class, '_getClassName', [__DIR__ . '/ValueObjectFixture.php']));
	}

	/** Список использованных объектов */
	public function testGetUsesList() {
		self::assertEquals([
			'Strings' => Strings::class,
			'ValueObject' => ValueObject::class,
			'CakeString' => String::class,
		], MethodMocker::callPrivate(ValueObjectDocumentation::class, '_getUsesList', [__DIR__ . '/ValueObjectFixture.php']));
	}

	/** Преобразовываем имя из namespace в PSR0 */
	public function testConvertPsr4ToPsr0() {
		self::assertEquals('Test_Object', MethodMocker::callPrivate(ValueObjectDocumentation::class, '_convertPsr4ToPsr0', ['\Test\Object']));
		self::assertEquals('Test_ObjectSecond', MethodMocker::callPrivate(ValueObjectDocumentation::class, '_convertPsr4ToPsr0', ['App\Test\ObjectSecond']));
		self::assertEquals('Test_ObjectThird', MethodMocker::callPrivate(ValueObjectDocumentation::class, '_convertPsr4ToPsr0', ['Test\ObjectThird']));
	}

	/** Определяем JS тип исходня из PHP типа */
	public function testGetJsVariableName() {
		self::assertEquals('Test_Object[]', MethodMocker::callPrivate(ValueObjectDocumentation::class, '_getJsVariableName', ['\Test\Object[]']));
		self::assertEquals('*', MethodMocker::callPrivate(ValueObjectDocumentation::class, '_getJsVariableName', [null]));
		self::assertEquals('boolean', MethodMocker::callPrivate(ValueObjectDocumentation::class, '_getJsVariableName', ['bool']));
		self::assertEquals('int[]', MethodMocker::callPrivate(ValueObjectDocumentation::class, '_getJsVariableName', ['integer[]']));
		self::assertEquals('float', MethodMocker::callPrivate(ValueObjectDocumentation::class, '_getJsVariableName', ['float']));
	}
}