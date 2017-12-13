<?php

namespace ArtSkills\Test\TestCase\TestSuite\Fixture;

use ArtSkills\ORM\Table;
use ArtSkills\TestSuite\Fixture\TestFixture;
use ArtSkills\TestSuite\Mock\PropertyAccess;
use ArtSkills\Test\Fixture\TestTableOneFixture;
use ArtSkills\TestSuite\AppTestCase;
use Cake\I18n\Time;

/**
 * @property Table $TestTableOne
 * @property Table $TestTableTwo
 * @property Table $TestTableThree
 */
class FixtureTest extends AppTestCase
{

	/** @inheritdoc */
	public $fixtures = [
		'test_table_one',
		// возможность писать без app
		'TestTableThree',
		// возможность в CamelCase
		'plugin.art_skills.test_table_two',
		// чтобы FixtureManager подтянул классовую фикстуру, которая в необычном неймспейсе
	];

	/**
	 * Тест на получение запросов на создание таблиц
	 */
	public function testGetStructure()
	{
		$expectedCreateTable =
			"CREATE TABLE `test_table_one` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'comment1',
  `col_enum` ENUM('val1','val2','val3') NOT NULL DEFAULT 'val1',
  `col_text` LONGTEXT NOT NULL,
  `col_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'comment2',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='description blabla'";

		$baseObject = new TestFixture('test_table_one');
		$baseObjectQuery = PropertyAccess::get($baseObject, '_createTableSqlQuery');
		self::assertEquals($expectedCreateTable, $baseObjectQuery, 'Неправильный запрос на создание таблицы из базового класса');

		$extendedObject = new TestTableOneFixture();
		$extendedObjectQuery = PropertyAccess::get($extendedObject, '_createTableSqlQuery');
		self::assertEquals($expectedCreateTable, $extendedObjectQuery, 'Неправильный запрос на создание таблицы из класса-наследника');
	}

	/**
	 * Тест на получение данных для загрузки в базу
	 */
	public function testGetData()
	{
		$defaultValue = [
			'id' => '45',
			'col_enum' => 'val3',
			'col_text' => 'olololo',
			'col_time' => '2017-03-14 22:33:44',
		];
		$localValue = [
			'id' => '158',
			'col_enum' => 'val2',
			'col_text' => 'qweqweqweqwe',
			'col_time' => '2017-03-14 11:22:33',
		];
		$classValue = [
			'id' => '10000',
			'col_enum' => 'val1',
			'col_text' => 'test test test',
			'col_time' => '2017-03-14 00:11:22',
		];

		$baseObjectDefault = new TestFixture('test_table_one');
		self::assertEquals([$defaultValue], $baseObjectDefault->records, 'Неправильные данные глобальной фикстуры');
		$baseObjectDefault->setTestCase(self::class);
		self::assertEquals([$localValue], $baseObjectDefault->records, 'Неправильные данные при переопределении фикстуры из глобальной в локальную');

		$baseObjectLocal = new TestFixture('test_table_one', self::class);
		self::assertEquals([$localValue], $baseObjectLocal->records, 'Неправильные данные локальной фикстуры');
		$baseObjectLocal->setTestCase(null);
		self::assertEquals([$defaultValue], $baseObjectLocal->records, 'Неправильные данные при переопределении фикстуры из локальной в глобальную');


		$extendedObject = new TestTableOneFixture();
		self::assertEquals([$classValue], $extendedObject->records, 'Неправильные данные фикстуры наследника');
	}

	/**
	 * Подтягивание моделей в свойства
	 */
	public function testTableModelLoad()
	{
		self::assertInstanceOf(\Cake\ORM\Table::class, $this->TestTableThree);
		self::assertInstanceOf('\TestApp\Model\Table\TestTableOneTable', $this->TestTableOne);
	}

	/**
	 * Тест корректной работы загрузки фикстур
	 */
	public function testFixtureLoad()
	{
		$res = $this->TestTableOne->find()->enableHydration(false)->toArray();
		self::assertEquals(
			[
				[
					'id' => '158',
					'col_enum' => 'val2',
					'col_text' => 'qweqweqweqwe',
					'col_time' => new Time('2017-03-14 11:22:33'),
				],
			],
			$res, 'Неправильно загрузилась локальная фикстура'
		);

		$res = $this->TestTableThree->find()->enableHydration(false)->toArray();
		self::assertEquals([['id' => '88']], $res, 'Неправильно загрузилась глобальная фикстура');

		$res = $this->TestTableTwo->find()->enableHydration(false)->toArray();
		self::assertEquals([
			[
				'id' => '11',
				'table_one_fk' => '1000',
				'col_text' => null,
			],
		], $res, 'Неправильно загрузилась фикстура наследника');
	}

}

