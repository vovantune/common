<?php

namespace ArtSkills\Test\TestCase\Lib\DeployerTest;

use ArtSkills\Lib\Shell;
use ArtSkills\Lib\Deployer;
use ArtSkills\Lib\Git;
use ArtSkills\Lib\Strings;
use ArtSkills\Log\Engine\SentryLog;
use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\Mock\MethodMocker;
use Cake\Log\Log;

/**
 * @covers \ArtSkills\Lib\Deployer
 * Тест создания объекта и метода деплой
 */
class DeployTest extends AppTestCase
{
	const VERSION_FILE_PATH = LocalDeployer::DIR_NEXT . DS . LocalDeployer::VERSION_FILE;
	const COPY_FILE_FROM = LocalDeployer::DIR_CURRENT . DS . LocalDeployer::COPY_FILE;
	const COPY_FILE_TO = LocalDeployer::DIR_NEXT . DS . LocalDeployer::COPY_FILE;

	/**
	 * История команд
	 *
	 * @var array
	 */
	private $_executeHistory = [];

	/**
	 * текущая папка
	 *
	 * @var string
	 */
	private $_currentDir = '';

	/**
	 * тестовое значение репы
	 *
	 * @var string
	 */
	private $_repo = '';

	/**
	 * текущая ветка
	 *
	 * @var string
	 */
	private $_branch = '';

	/**
	 * ожидаемый следующий корень для нормальных тестов
	 *
	 * @var string
	 */
	private $_nextRoot = '';

	/**
	 * ожидаемая подпапка для нормальных тестов
	 *
	 * @var string
	 */
	private $_nextRootSub = '';

	/**
	 * Тестовое значение версии
	 *
	 * @var int
	 */
	private $_version = 3;


	/** @inheritdoc */
	public function setUp() {
		parent::setUp();
		$this->_executeHistory = [];
		$this->_cleanFiles();

		$this->_currentDir = getcwd();
		$this->_repo = LocalDeployer::REPO_NAME;
		$this->_branch = Git::getInstance()->getCurrentBranchName();
		$this->_version += 2; // изменяю, чтоб в разных тестах были разные значения, на всякий случай
		$this->_nextRoot = LocalDeployer::DIR_NEXT;
		$this->_nextRootSub = $this->_nextRoot . DS . LocalDeployer::CAKE_SUB_PATH;
	}

	/** @inheritdoc */
	public function tearDown() {
		$this->_cleanFiles();
		parent::tearDown();
	}

	/** удалить ненужные файлы */
	private function _cleanFiles() {
		$toClean = [self::VERSION_FILE_PATH, self::COPY_FILE_TO];
		foreach ($toClean as $file) {
			if (file_exists($file)) {
				unlink($file);
			}
		}
	}

	/**
	 * Обычный деплой с ротацией
	 * Смотрим выполняемые команды
	 */
	public function testDeploy() {
		$mainRoot = LocalDeployer::DIR_MAIN;

		$this->_mockExec(6);
		$this->_mockOther();

		$deployer = new LocalDeployer();
		$res = $deployer->deploy($this->_repo, $this->_branch, '', $this->_version);
		self::assertTrue($res);

		$expectedCommandList = [
			"cd '{$this->_nextRootSub}' 2>&1 && git rev-parse --abbrev-ref HEAD 2>&1",
			"cd {$this->_nextRootSub}",
			"cp '" . self::COPY_FILE_FROM . "' '" . self::COPY_FILE_TO . "' 2>&1",
			'git pull 2>&1',
			'putenv HOME=/var/www',
			"php composer.phar update 'artskills/common' --optimize-autoloader --no-dev --no-interaction 2>&1",
			'vendor/bin/phinx migrate 2>&1',
			"ln -snf '{$this->_nextRoot}' '$mainRoot' 2>&1",
			"cd {$this->_currentDir}",
		];
		self::assertEquals($expectedCommandList, $this->_executeHistory);
		$this->assertFileEqualsString((string)($this->_version + 1), self::VERSION_FILE_PATH);
		self::assertFileEquals(self::COPY_FILE_FROM, self::COPY_FILE_TO);
	}

	/** Деплой в текущую папку */
	public function testDeploySingleRoot() {
		$singleRoot = __DIR__;
		$rootSub = $singleRoot . DS . LocalDeployer::CAKE_SUB_PATH;

		$this->_mockExec(4);
		$this->_mockOther();

		$deployer = new LocalDeployer([
			'_singleRoot' => $singleRoot,
			'_mainRoot' => '',
			'_currentRoot' => '',
			'_rotateDeployFolders' => [],
			'_versionFile' => $singleRoot . DS . LocalDeployer::VERSION_FILE,
		]);
		$res = $deployer->deploy($this->_repo, $this->_branch, '', $this->_version);
		self::assertTrue($res);

		$expectedCommandList = [
			"cd '$rootSub' 2>&1 && git rev-parse --abbrev-ref HEAD 2>&1",
			"cd $rootSub",
			'git pull 2>&1',
			'putenv HOME=/var/www',
			"php composer.phar update 'artskills/common' --optimize-autoloader --no-dev --no-interaction 2>&1",
			'vendor/bin/phinx migrate 2>&1',
			"cd {$this->_currentDir}",
		];
		self::assertEquals($expectedCommandList, $this->_executeHistory);
		$this->assertFileEqualsString((string)($this->_version + 1), self::VERSION_FILE_PATH);
	}

	/** Что если не удалось спуллиться */
	public function testPullFail() {
		$this->_mockExec(3, '/^git pull 2>&1$/');
		$this->_mockOther(2, 0, 0, 1);
		$this->_expectException('Не удалось спуллиться');

		$deployer = new LocalDeployer();
		$res = $deployer->deploy($this->_repo, $this->_branch, '', $this->_version);
		self::assertFalse($res);

		$expectedCommandList = [
			"cd '{$this->_nextRootSub}' 2>&1 && git rev-parse --abbrev-ref HEAD 2>&1",
			"cd {$this->_nextRootSub}",
			"cp '" . self::COPY_FILE_FROM . "' '" . self::COPY_FILE_TO . "' 2>&1",
			'git pull 2>&1',
			"cd {$this->_currentDir}",
		];
		self::assertEquals($expectedCommandList, $this->_executeHistory);
	}

	/** Что если не обновился композер */
	public function testComposerFail() {
		$this->_mockExec(4, '/^php composer.phar update/');
		$this->_mockOther(2, 1, 0, 1);
		$this->_expectException('Не удалось обновить композер');

		$deployer = new LocalDeployer();
		$res = $deployer->deploy($this->_repo, $this->_branch, '', $this->_version);
		self::assertFalse($res);

		$expectedCommandList = [
			"cd '{$this->_nextRootSub}' 2>&1 && git rev-parse --abbrev-ref HEAD 2>&1",
			"cd {$this->_nextRootSub}",
			"cp '" . self::COPY_FILE_FROM . "' '" . self::COPY_FILE_TO . "' 2>&1",
			'git pull 2>&1',
			'putenv HOME=/var/www',
			"php composer.phar update 'artskills/common' --optimize-autoloader --no-dev --no-interaction 2>&1",
			"cd {$this->_currentDir}",
		];
		self::assertEquals($expectedCommandList, $this->_executeHistory);
	}

	/** Что если не развернулись миграции */
	public function testMigrateFail() {
		$this->_mockExec(5, '/^vendor\/bin\/phinx migrate 2>&1$/');
		$this->_mockOther(2, 1, 0, 1);
		$this->_expectException('Миграции не развернулись');

		$deployer = new LocalDeployer();
		$res = $deployer->deploy($this->_repo, $this->_branch, '', $this->_version);
		self::assertFalse($res);

		$expectedCommandList = [
			"cd '{$this->_nextRootSub}' 2>&1 && git rev-parse --abbrev-ref HEAD 2>&1",
			"cd {$this->_nextRootSub}",
			"cp '" . self::COPY_FILE_FROM . "' '" . self::COPY_FILE_TO . "' 2>&1",
			'git pull 2>&1',
			'putenv HOME=/var/www',
			"php composer.phar update 'artskills/common' --optimize-autoloader --no-dev --no-interaction 2>&1",
			'vendor/bin/phinx migrate 2>&1',
			"cd {$this->_currentDir}",
		];
		self::assertEquals($expectedCommandList, $this->_executeHistory);
	}

	/**
	 * Мокаем выполнение консольных команд
	 *
	 * @param int $expectTimes
	 * @param string|bool $failPattern для каких команд возвращать неудачу
	 * @throws \Exception
	 */
	private function _mockExec($expectTimes, $failPattern = false) {
		MethodMocker::mock(Shell::class, '_exec')
			->expectCall($expectTimes)
			->willReturnAction(
				function ($args) use($failPattern) {
					$command = $args[0];
					$this->_executeHistory[] = $command;
					if (
						preg_match('/^(cd [^&]+(&1)?\s+&&\s)?git (branch( -[ar])?|for-each-ref.*|rev-parse.*)/', $command)
						|| Strings::startsWith($command, 'cp ')
					) {
						exec($args[0], $output, $returnCode);
						return [$returnCode === 0, $output];
					} elseif ($failPattern && preg_match($failPattern, $command)) {
						return [false, []];
					} else {
						return [true, []];
					}
				}
			);
	}

	/**
	 * Мокаем выполнение ещё всякой мелочи
	 *
	 * @param int $expectTimesChdir
	 * @param int $expectTimesPutenv
	 * @param int $expectTimesLog
	 * @param int $expectFileWrite
	 * @throws \Exception
	 */
	private function _mockOther(
		$expectTimesChdir = 2, $expectTimesPutenv = 1, $expectTimesLog = 1, $expectFileWrite = 1
	) {
		MethodMocker::sniff(Deployer::class, '_chdir')
			->expectCall($expectTimesChdir)
			->willReturnAction(function ($args) {
				$this->_executeHistory[] = 'cd ' . $args[0];
			});

		MethodMocker::mock(Deployer::class, '_putEnv')
			->expectCall($expectTimesPutenv)
			->willReturnAction(function ($args) {
				$this->_executeHistory[] = 'putenv ' . $args[0];
			});

		MethodMocker::mock(Log::class, 'info')->expectCall($expectTimesLog);

		MethodMocker::sniff(Deployer::class, '_updateVersion')->expectCall($expectFileWrite);
	}

	/**
	 * Ожидаем, что в тесте вылетит исключение с заданным сообщением
	 *
	 * @param string $message
	 */
	private function _expectException($message) {
		MethodMocker::mock(SentryLog::class, 'logException')
			->singleCall()
			->willReturnAction(function ($args) use($message) {
				/** @var \Exception $exception */
				$exception = $args[0];
				self::assertContains($message, $exception->getMessage());
			});
	}







	/**
	 * Проверяем, что ничего не произошло
	 *
	 * @param array ...$deployArgs аргументы для вызова
	 */
	private function _testNothingHappens(...$deployArgs) {
		$this->_mockExec(1);
		$this->_mockOther(2, 0, 0, 0);

		$deployer = new LocalDeployer();
		$res = $deployer->deploy(...$deployArgs);
		self::assertFalse($res);

		$expectedCommandList = [
			"cd '{$this->_nextRootSub}' 2>&1 && git rev-parse --abbrev-ref HEAD 2>&1",
			"cd {$this->_nextRootSub}",
			"cd {$this->_currentDir}",
		];
		self::assertEquals($expectedCommandList, $this->_executeHistory);
	}

	/** не та репа */
	public function testWrongRepo() {
		$this->_testNothingHappens('badRepoName', $this->_branch, '', $this->_version);
	}

	/** не та ветка */
	public function testWrongBranch() {
		$this->_testNothingHappens($this->_repo, 'branchNameThatWillNeverEverExist', '', $this->_version);
	}

	/** не то окружение */
	public function testWrongEnv() {
		MethodMocker::mock(LocalDeployer::class, '_isDeployEnvironment')->willReturnValue(false);
		$this->_testNothingHappens($this->_repo, $this->_branch, '', $this->_version);
	}

	/** не мигрируем по-умолчанию (например, в тестовом окружении) и не разворачиваем композер */
	public function testNoAutoMigrateNoComposer() {
		$mainRoot = LocalDeployer::DIR_MAIN;

		$this->_mockExec(4);
		$this->_mockOther(2, 0, 1, 1);

		$deployer = new LocalDeployer([
			'_autoMigrate' => false,
			'_composerCommand' => false,
		]);
		$res = $deployer->deploy($this->_repo, $this->_branch, '', $this->_version);
		self::assertTrue($res);

		$expectedCommandList = [
			"cd '{$this->_nextRootSub}' 2>&1 && git rev-parse --abbrev-ref HEAD 2>&1",
			"cd {$this->_nextRootSub}",
			"cp '" . self::COPY_FILE_FROM . "' '" . self::COPY_FILE_TO . "' 2>&1",
			'git pull 2>&1',
			"ln -snf '{$this->_nextRoot}' '$mainRoot' 2>&1",
			"cd {$this->_currentDir}",
		];
		self::assertEquals($expectedCommandList, $this->_executeHistory);
	}

	/** не перечислены зависимости и не надо копировать файлы */
	public function testNoDependenciesNoCopy() {
		$mainRoot = LocalDeployer::DIR_MAIN;

		$this->_mockExec(4);
		$this->_mockOther(2, 0, 1, 1);

		$deployer = new LocalDeployer([
			'_composerDependencies' => false,
			'_copyFileList' => [],
		]);
		$res = $deployer->deploy($this->_repo, $this->_branch, '', $this->_version);
		self::assertTrue($res);

		$expectedCommandList = [
			"cd '{$this->_nextRootSub}' 2>&1 && git rev-parse --abbrev-ref HEAD 2>&1",
			"cd {$this->_nextRootSub}",
			'git pull 2>&1',
			'vendor/bin/phinx migrate 2>&1',
			"ln -snf '{$this->_nextRoot}' '$mainRoot' 2>&1",
			"cd {$this->_currentDir}",
		];
		self::assertEquals($expectedCommandList, $this->_executeHistory);
	}

	/** не указан composer home, файл версии и опции композера */
	public function testNoHomeNoVersionNoOptions() {
		$mainRoot = LocalDeployer::DIR_MAIN;

		$this->_mockExec(6);
		$this->_mockOther(2, 0, 1, 1);

		$deployer = new LocalDeployer([
			'_versionFile' => '',
			'_composerHome' => '',
			'_composerOptions' => [],
			'_composerRequireDev' => true,
		]);
		$res = $deployer->deploy($this->_repo, $this->_branch, '', $this->_version);
		self::assertTrue($res);

		$expectedCommandList = [
			"cd '{$this->_nextRootSub}' 2>&1 && git rev-parse --abbrev-ref HEAD 2>&1",
			"cd {$this->_nextRootSub}",
			"cp '" . self::COPY_FILE_FROM . "' '" . self::COPY_FILE_TO . "' 2>&1",
			'git pull 2>&1',
			"php composer.phar update 'artskills/common' --no-interaction 2>&1",
			'vendor/bin/phinx migrate 2>&1',
			"ln -snf '{$this->_nextRoot}' '$mainRoot' 2>&1",
			"cd {$this->_currentDir}",
		];
		self::assertEquals($expectedCommandList, $this->_executeHistory);
		self::assertFileNotExists(self::VERSION_FILE_PATH);
	}




	/**
	 * конфликт конфига
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Заполнены конфликтующие свойства
	 */
	public function testConfigConflict() {
		new LocalDeployer([
			'_singleRoot' => ROOT,
		]);
	}

	/**
	 * не указан automigrate
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Нужно явно указать параметр _autoMigrate
	 */
	public function testUnsetAutoMigrate() {
		new LocalDeployer([
			'_autoMigrate' => null,
		]);
	}

	/**
	 * Явно задан параметр миграции, но не задана команда
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Явно задан параметр миграции, но не задана команда
	 */
	public function testBadAutoMigrate() {
		new LocalDeployer([
			'_autoMigrate' => true,
			'_phinxCommand' => '',
		]);
	}


	/**
	 * текущий корень отсутствует в списке
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Текущий корень проекта отсутствует в списке
	 */
	public function testBadCurrentRoot() {
		new LocalDeployer([
			'_currentRoot' => '/var/www/common-15',
		]);
	}

	/**
	 * не указан главный симлинк
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Не указан главный симлинк
	 */
	public function testNoMainRoot() {
		new LocalDeployer([
			'_mainRoot' => '',
		]);
	}

	/**
	 * главный симлинк в списке
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Главный симлинк задан в списке папок
	 */
	public function testMainRootInList() {
		new LocalDeployer([
			'_mainRoot' => ROOT,
		]);
	}

	/**
	 * не проинициализировался гит
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Не проинициализировался гит
	 */
	public function testGitInitFail() {
		MethodMocker::mock(Git::class, 'getCurrentBranchName')->willReturnValue('');
		new LocalDeployer();
	}

	/**
	 * не указан репозиторий
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Не указан репозиторий
	 */
	public function testNoRepo() {
		new LocalDeployer([
			'_repoName' => '',
		]);
	}

	/**
	 * только одна папка в списке
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage явно задайте свойство _singleRoot
	 */
	public function testOneFolderInList() {
		new LocalDeployer([
			'_rotateDeployFolders' => [ROOT],
		]);
	}

	/**
	 * дубли в списке папок
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage В списке папок есть дубли
	 */
	public function testFolderDuplicates() {
		new LocalDeployer([
			'_rotateDeployFolders' => [ROOT, ROOT],
		]);
	}




}