<?php

namespace ArtSkills\Test\TestCase\EntityBuilder;

use ArtSkills\Filesystem\Folder;
use ArtSkills\EntityBuilder\EntityBuilder;
use ArtSkills\EntityBuilder\EntityBuilderConfig;
use ArtSkills\EntityBuilder\TableDocumentation;
use ArtSkills\TestSuite\AppTestCase;

class EntityBuilderTest extends AppTestCase
{
	const MODEL_FOLDER = 'Model';
	const MODEL_PATH = APP . self::MODEL_FOLDER;

	/**
	 * @inheritdoc
	 */
	public $fixtures = [
		'app.test_table_one',
		'app.test_table_two',
		'app.test_table_three',
		'app.test_table_four',
	];

	/**
	 * Восстановить содержимое папки Model
	 */
	public static function restoreModelFolder($beforeTest)
	{
		$modelFolder = new Folder(self::MODEL_PATH);
		$backupFolder = new Folder(__DIR__ . '/Backup');
		if ($backupFolder->exists()) {
			// если существует папка бекапа, то восстанавливаем модели из неё
			// после теста бекап удаляем
			$modelFolder->delete();
			$modelFolder->createSelf();
			$backupFolder->copy($modelFolder->path);
			if (!$beforeTest) {
				$backupFolder->delete();
			}
		} else {
			// если папки бекапа не существует, то бекапим модели
			$backupFolder->createSelf();
			$modelFolder->copy($backupFolder->path);
		}
	}

	/** @inheritdoc */
	public function setUp()
	{
		parent::setUp();

		self::restoreModelFolder(true);
		EntityBuilderConfig::create()
			->setModelFolder(self::MODEL_PATH . DS)
			->setModelNamespace('TestApp\\' . self::MODEL_FOLDER)
			->register();
	}

	/** @inheritdoc */
	public function tearDown()
	{
		parent::tearDown();
		EntityBuilder::setConfig(null);
		TableDocumentation::setConfig(null);
		self::restoreModelFolder(false);
	}

	/**
	 * без конфига
	 */
	public function testNoConfig()
	{
		$this->expectExceptionMessage("Не задан конфиг");
		$this->expectException(\Exception::class);
		EntityBuilder::setConfig(null);
		EntityBuilder::build();
	}

	/**
	 * плохой конфиг
	 */
	public function testBadConfig()
	{
		$this->expectExceptionMessage("Empty value for field 'modelFolder'");
		$this->expectException(\Exception::class);
		EntityBuilderConfig::create()->register();
		EntityBuilder::build();
	}


	/**
	 * Обновление существующих таблиц и создание для них всего, что нужно
	 */
	public function testBuild()
	{
		/**
		 * table_one - всё существовало, всё изменилось
		 * table_two - всё существовало, ничего не изменилось
		 * table_three - следующий тест - был только пустой класс таблицы, всё создалось
		 * table_four - не был и не создался
		 */
		$hasChanges = EntityBuilder::build();
		$expectedFolder = new Folder(__DIR__ . '/ExpectedResults/BuildEntities');
		$expectedFiles = $expectedFolder->tree()[1];
		foreach ($expectedFiles as $expectedFile) {
			$actualFile = str_replace($expectedFolder->path, self::MODEL_PATH, $expectedFile);
			self::assertFileEquals($expectedFile, $actualFile, 'Неправильно сработал построитель сущностей: ' . $expectedFile);
		}
		self::assertTrue($hasChanges, 'Построитель не сказал, что были изменения');
	}

	/**
	 * Файл уже есть
	 */
	public function testCreateExists()
	{
		$this->expectExceptionMessage("TestTableTwoTable.php already exists");
		$this->expectException(\Exception::class);
		EntityBuilder::createTableClass('test_table_two');
	}

	/**
	 * Такой таблицы нет
	 */
	public function testCreateBad()
	{
		$this->expectExceptionMessage("Table \"bad_table\" does not exist in DB");
		$this->expectException(\Exception::class);
		EntityBuilder::createTableClass('bad_table');
	}

	/**
	 * Создание новой таблицы
	 */
	public function testCreate()
	{
		$expectedFilePath = self::MODEL_PATH . '/Table/TestTableFourTable.php';
		$actualFilePath = EntityBuilder::createTableClass('test_table_four');
		self::assertEquals($expectedFilePath, $actualFilePath);
		self::assertFileEquals(__DIR__ . '/ExpectedResults/CreateTable/TestTableFourTable.php', $actualFilePath, 'Ошибка создания нового класса таблицы');

		// очень плохое решение, но я не придумал ничего лучше
		require_once $actualFilePath;
		/**
		 * table_three - был только пустой класс таблицы, всё создалось
		 */
		$hasChanges = EntityBuilder::build();
		$expectedFolder = new Folder(__DIR__ . '/ExpectedResults/CreateTableBuild');
		$expectedFiles = $expectedFolder->tree()[1];
		foreach ($expectedFiles as $expectedFile) {
			$actualFile = str_replace($expectedFolder->path, self::MODEL_PATH, $expectedFile);
			self::assertFileEquals($expectedFile, $actualFile, 'Неправильно сработал построитель сущностей ' . $expectedFile);
		}
		self::assertTrue($hasChanges, 'Построитель не сказал, что были изменения');
	}


}