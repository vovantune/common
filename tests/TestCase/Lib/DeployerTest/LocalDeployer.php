<?php
namespace ArtSkills\Test\TestCase\Lib\DeployerTest;

use ArtSkills\Lib\Deployer;
use ArtSkills\Lib\Env;

class LocalDeployer extends Deployer
{

	// константы для использования в тесте
	const REPO_NAME = 'common';
	const DIR_CURRENT = ROOT;
	const DIR_NEXT = __DIR__;
	const DIR_MAIN = '/var/www/common';
	const CAKE_SUB_PATH = 'subfolder';
	const VERSION_FILE = self::CAKE_SUB_PATH . DS . 'version.txt';
	const VERSION_FILE_PATH = self::DIR_CURRENT . DS . self::VERSION_FILE;
	const COPY_FILE = 'composer.json';

	/** @inheritdoc */
	protected $_repoName = self::REPO_NAME;

	/** @inheritdoc */
	protected $_mainRoot = self::DIR_MAIN;

	/** @inheritdoc */
	protected $_currentRoot = self::DIR_CURRENT;

	/** @inheritdoc */
	protected $_rotateDeployFolders = [
		'/var/www/common-1',
		self::DIR_CURRENT,
		self::DIR_NEXT,
		'/var/www/common-3',
	];

	/** @inheritdoc  */
	protected $_cakeSubPath = self::CAKE_SUB_PATH;

	/** @inheritdoc  */
	protected $_versionFile = self::VERSION_FILE_PATH;

	/** @inheritdoc */
	protected $_autoMigrate = true;

	/** @inheritdoc */
	protected $_copyFileList = [
		self::DIR_CURRENT . DS . self::COPY_FILE,
	];

	/**
	 * Переопределение свойств для тестов
	 * @param array $setProperties
	 */
	public function __construct(array $setProperties = []) {
		foreach ($setProperties as $name => $value) {
			$this->{$name} = $value;
		}
		parent::__construct();
	}

	/** @inheritdoc */
	protected function _isDeployEnvironment() {
		return Env::isUnitTest();
	}

}