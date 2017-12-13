<?php

namespace ArtSkills\Test\TestCase\Lib\DeployerTest;

use ArtSkills\Filesystem\Folder;
use ArtSkills\Lib\Deployer;
use ArtSkills\TestSuite\AppTestCase;

/**
 * @covers \ArtSkills\Lib\Deployer
 * Тест создания ссылок
 */
class MakeLinkTest extends AppTestCase
{
	const TEST_FOLDER = __DIR__ . DS . 'link_test';
	const TEST_PROJECT_FOLDER = self::TEST_FOLDER . DS . 'test_project';
	const TEST_NEW_FOLDER = 'actual_project';
	const TEST_NEW_FOLDER_FULL = self::TEST_FOLDER . DS . self::TEST_NEW_FOLDER;

	/** @inheritdoc */
	public function setUp()
	{
		$this->_clean();
		parent::setUp();
	}

	/** @inheritdoc */
	public function tearDown()
	{
		$this->_clean();
		parent::tearDown();
	}

	/** Очистить тестовую папку */
	private function _clean()
	{
		$folder = new Folder(self::TEST_FOLDER);
		$folder->delete();
		$folder->createSelf();
	}

	/** Тест создания ссылок */
	public function testCreate()
	{
		mkdir(self::TEST_PROJECT_FOLDER);
		Deployer::makeProjectSymlink(self::TEST_PROJECT_FOLDER, self::TEST_NEW_FOLDER);
		clearstatcache();
		self::assertDirectoryExists(self::TEST_NEW_FOLDER_FULL);
		self::assertTrue(is_link(self::TEST_PROJECT_FOLDER));
		self::assertEquals(self::TEST_NEW_FOLDER_FULL, readlink(self::TEST_PROJECT_FOLDER));
	}

	/**
	 * Исходная папка - уже симлинк
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Передан некорректный каталог проекта
	 */
	public function testFailLink()
	{
		$folderName = self::TEST_PROJECT_FOLDER . '1';
		mkdir($folderName);
		symlink($folderName, self::TEST_PROJECT_FOLDER);
		self::assertTrue(is_link(self::TEST_PROJECT_FOLDER));
		Deployer::makeProjectSymlink(self::TEST_PROJECT_FOLDER, self::TEST_NEW_FOLDER);
	}

	/**
	 * Исходная папка - не папка
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Передан некорректный каталог проекта
	 */
	public function testFailNotFolder()
	{
		file_put_contents(self::TEST_PROJECT_FOLDER, 'test');
		Deployer::makeProjectSymlink(self::TEST_PROJECT_FOLDER, self::TEST_NEW_FOLDER);
	}

	/**
	 * Папка назначения уже существует
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Такой каталог уже есть
	 */
	public function testFailExists()
	{
		mkdir(self::TEST_PROJECT_FOLDER);
		mkdir(self::TEST_NEW_FOLDER_FULL);
		Deployer::makeProjectSymlink(self::TEST_PROJECT_FOLDER, self::TEST_NEW_FOLDER);
	}


}