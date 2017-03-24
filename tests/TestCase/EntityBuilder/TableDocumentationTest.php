<?php
namespace ArtSkills\Test\TestCase\EntityBuilder;

use ArtSkills\Filesystem\Folder;
use ArtSkills\EntityBuilder\EntityBuilder;
use ArtSkills\EntityBuilder\EntityBuilderConfig;
use ArtSkills\EntityBuilder\TableDocumentation;
use ArtSkills\TestSuite\AppTestCase;

class TableDocumentationTest extends AppTestCase
{
	/**
	 * @inheritdoc
	 */
	public $fixtures = [
		'app.test_table_one',
		'app.test_table_two',
		'app.test_table_three',
	];

	/** @inheritdoc */
	public function setUp() {
		parent::setUp();

		EntityBuilderTest::restoreModelFolder(true);
		EntityBuilderConfig::create()
			->setModelFolder(EntityBuilderTest::MODEL_PATH . DS)
			->setModelNamespace('TestApp\\' . EntityBuilderTest::MODEL_FOLDER)
			->register();
	}

	/** @inheritdoc */
	public function tearDown() {
		parent::tearDown();
		EntityBuilder::setConfig(null);
		TableDocumentation::setConfig(null);
		EntityBuilderTest::restoreModelFolder(false);
	}

	/**
	 * без конфига
	 * @expectedException \Exception
	 * @expectedExceptionMessage Не задан конфиг
	 */
	public function testNoConfig() {
		TableDocumentation::setConfig(null);
		TableDocumentation::build();
	}

	/**
	 * плохой конфиг
	 * @expectedException \Exception
	 * @expectedExceptionMessage Empty value for field 'modelFolder'
	 */
	public function testBadConfig() {
		EntityBuilderConfig::create()->register();
		TableDocumentation::build();
	}

	/**
	 * Обновление существующих таблиц и создание для них всего, что нужно
	 */
	public function testBuild() {
		/**
		 * Он работает на основе существующих классов. если в них неактуальные комменты, то они и останутся
		 * изменяет только 2 файла доков
		 */
		(new Folder(__DIR__ . '/Fixture'))->copy(EntityBuilderTest::MODEL_PATH);
		$hasChanges = TableDocumentation::build();
		$expectedFolder = new Folder(__DIR__ . '/ExpectedResults/BuildDocs');
		$expectedFiles = $expectedFolder->read()[1];
		foreach ($expectedFiles as $fileName) {
			$expectedFile = __DIR__ . '/ExpectedResults/BuildDocs/' . $fileName;
			$actualFile = APP . EntityBuilderTest::MODEL_FOLDER . '/' . $fileName;
			self::assertFileEquals($expectedFile, $actualFile, 'Неправильно сработал построитель документации');
		}
		self::assertTrue($hasChanges, 'Построитель не сказал, что были изменения');
	}
}