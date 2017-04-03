<?php
namespace ArtSkills\Test\TestCase\Lib\DeployTest;

use ArtSkills\Lib\Git;
use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\Mock\MethodMocker;
use Cake\Log\Log;

class DeployTest extends AppTestCase
{
	/**
	 * История команд
	 * @var array
	 */
	private $_executeHistory = [];

	/** @inheritdoc */
	public function setUp() {
		parent::setUp();
		$this->_executeHistory = [];
	}

	/**
	 * Смотрим выполняемые команды
	 */
	public function testRun() {
		$repo = 'common';
		$branch = Git::getInstance()->getCurrentBranchName();

		MethodMocker::mock(Log::class, 'info')->singleCall();
		$this->_mockExecGit(1);
		$this->_mockExecDeploy(2);

		$res = LocalDeploy::getInstance()->run($repo, $branch, '');
		self::assertTrue($res);
		$expectedCommandList = [
			'php composer.phar "artskills/common"',
    		'git pull',
    		'vendor/bin/phinx migrate',
		];
		self::assertEquals($expectedCommandList, $this->_executeHistory);
	}

	/** Не должно запуститься */
	public function testNotRun() {
		$repo = 'common';
		$branch = Git::getInstance()->getCurrentBranchName();

		MethodMocker::mock(Log::class, 'info')->expectCall(0);
		$this->_mockExecGit(0);
		$this->_mockExecDeploy(0);

		$res = LocalDeploy::getInstance()->run($repo, 'not' . $branch, '');
		self::assertFalse($res);

		$res = LocalDeploy::getInstance()->run('not' . $repo, $branch, '');
		self::assertFalse($res);
	}

	/**
	 * Мокаем _execute в Git
	 *
	 * @param int $expectTimes
	 * @throws \Exception
	 */
	private function _mockExecGit($expectTimes) {
		MethodMocker::mock(Git::class, '_execute')
			->expectCall($expectTimes)
			->willReturnAction(function ($args) {
				$this->_executeHistory[] = $args[0];
				if (preg_match('/^git (branch( -[ar])?|for-each-ref.*)$/', $args[0])) {
					exec($args[0], $output);
					return $output;
				} else {
					return [];
				}
			});
	}

	/**
	 * Мокаем _execute в Deploy
	 *
	 * @param int $expectTimes
	 * @throws \Exception
	 */
	private function _mockExecDeploy($expectTimes) {
		MethodMocker::mock(LocalDeploy::class, '_exec')
			->expectCall($expectTimes)
			->willReturnAction(function ($args) {
				$this->_executeHistory[] = $args[0];
			});
	}


}