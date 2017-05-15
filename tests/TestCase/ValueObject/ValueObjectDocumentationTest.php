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
		ValueObjectDocumentation::buildJsDoc(__DIR__ . '/ValueObjectFixture.php', TMP);
		$jsDocFile = TMP . 'ArtSkills_Test_TestCase_ValueObject_ValueObjectFixture.js';
		self::assertFileExists($jsDocFile);
		self::assertEquals("// Auto generated file, to change structure edit ArtSkills\Test\TestCase\ValueObject\ValueObjectFixture php class
/**
 * @typedef {Object} ArtSkills_Test_TestCase_ValueObject_ValueObjectFixture
 * @property {string} field1 блаблабла трололо
 * @property field2
 * @property {ArtSkills_Lib_Strings} field3
 * @property {Cake_Utility_String} field4
 */
", file_get_contents($jsDocFile));
		unlink($jsDocFile);
	}

	/** Дока с наследованием */
	public function testMainInheritance() {
		ValueObjectDocumentation::buildJsonSchema(__DIR__ . '/ValueObjectFixtureSecond.php', TMP, 'https://www.artskills.ru/jsonSchema/');
		$jsDocFile = TMP . 'ArtSkills_Test_TestCase_ValueObject_ValueObjectFixtureSecond.json';
		self::assertFileExists($jsDocFile);
		self::assertEquals([
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title' => 'ArtSkills_Test_TestCase_ValueObject_ValueObjectFixtureSecond',
			'description' => 'ArtSkills\Test\TestCase\ValueObject\ValueObjectFixtureSecond php class',
			'type' => 'object',
			'properties' => [
				'thisProperty' => [
					'oneOf' => [
						0 => [
							'type' => 'null',
						],
						1 => [
							'$ref' => 'https://www.artskills.ru/jsonSchema/ArtSkills_Test_TestCase_ValueObject_ValueObjectFixtureSecond.json',
						],
					],
				],
				'multiplyProperty' => [
					'oneOf' => [
						0 => [
							'type' => 'null',
						],
						1 => [
							'type' => 'integer',
						],
						2 => [
							'type' => 'string',
						],
					],
				],
				'intArray' => [
					'oneOf' => [
						0 => [
							'type' => 'null',
						],
						1 => [
							'type' => 'array',
							'items' => [
								'type' => 'integer',
							],
							'minItems' => 0,
						],
					],
				],
				'arrayArray' => [
					'oneOf' => [
						0 => [
							'type' => 'null',
						],
						1 => [
							'type' => 'object',
						],
					],
				],
				'mixedProperty' => [
					'oneOf' => [
						0 => [
							'type' => 'null',
						],
						1 => [
							'description' => '',
						],
					],
				],
				'field1' => [
					'oneOf' => [
						0 => [
							'type' => 'null',
						],
						1 => [
							'type' => 'string',
							'description' => 'блаблабла трололо',
						],
					],
				],
				'field2' => [
					'oneOf' => [
						0 => [
							'type' => 'null',
						],
						1 => [
							'description' => "\nТип данных у свойства \"ArtSkills\Test\TestCase\ValueObject\ValueObjectFixtureSecond::field2\" не описан.",
						],
					],
				],
				'field3' => [
					'oneOf' => [
						0 => [
							'type' => 'null',
						],
						1 => [
							'$ref' => 'https://www.artskills.ru/jsonSchema/ArtSkills_Test_TestCase_ValueObject_Strings.json',
						],
					],
				],
				'field4' => [
					'oneOf' => [
						0 => [
							'type' => 'null',
						],
						1 => [
							'$ref' => 'https://www.artskills.ru/jsonSchema/ArtSkills_Test_TestCase_ValueObject_CakeString.json',
						],
					],
				],
			],
		], json_decode(file_get_contents($jsDocFile), true));
		unlink($jsDocFile);
	}

	/** Список использованных объектов */
	public function testGetUsesList() {
		self::assertEquals([
			'Strings' => Strings::class,
			'ValueObject' => ValueObject::class,
			'CakeString' => String::class,
		], MethodMocker::callPrivate(ValueObjectDocumentation::class, '_getUsesList', [__DIR__ . '/ValueObjectFixture.php']));
	}
}