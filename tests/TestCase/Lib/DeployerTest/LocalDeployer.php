<?php

namespace ArtSkills\Test\TestCase\Lib\DeployerTest;

use ArtSkills\Lib\Deployer;
use ArtSkills\Lib\Env;

class LocalDeployer extends Deployer
{

    // константы для использования в тесте
    const REPO_NAME = 'common';
    const DIR_CURRENT = __DIR__ . DS . 'project-1';
    const DIR_NEXT = __DIR__ . DS . 'project-2';
    const SYMLINK = __DIR__ . DS . 'symlink';
    const CAKE_SUB_PATH = 'subfolder';
    const VERSION_FILE = self::CAKE_SUB_PATH . DS . 'version.txt';
    const VERSION_FILE_PATH = self::DIR_CURRENT . DS . self::VERSION_FILE;
    const COPY_FILE = self::CAKE_SUB_PATH . DS . 'some_file.txt';
    const VERSION = 5;

    /** @inheritdoc */
    protected $_repoName = self::REPO_NAME;

    /** @inheritdoc */
    protected $_projectSymlink = self::SYMLINK;

    /** @inheritdoc */
    protected $_rotateDeployFolders = [
        '/var/www/common-1',
        self::DIR_CURRENT,
        self::DIR_NEXT,
        '/var/www/common-3',
    ];

    /** @inheritdoc */
    protected $_cakeSubPath = self::CAKE_SUB_PATH;

    /** @inheritdoc */
    protected $_versionFile = self::VERSION_FILE_PATH;

    /** @inheritdoc */
    protected $_autoMigrate = true;

    /** @inheritdoc */
    protected $_copyFileList = [
        self::DIR_CURRENT . DS . self::COPY_FILE,
    ];

    /** @inheritdoc */
    public function __construct(array $config = [])
    {
        if (!array_key_exists('isDeployEnv', $config)) {
            $config['isDeployEnv'] = true;
        }
        if (!array_key_exists('versionFile', $config)) {
            $config['versionFile'] = self::VERSION_FILE_PATH;
        }
        $config['currentVersion'] = self::VERSION;
        parent::__construct($config);
    }
}
