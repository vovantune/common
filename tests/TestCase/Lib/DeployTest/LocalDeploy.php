<?php
namespace ArtSkills\Test\TestCase\Lib\DeployTest;

use ArtSkills\Lib\Deploy;
use ArtSkills\Traits\Singleton;

class LocalDeploy extends Deploy
{
	use Singleton;

	/** @inheritdoc */
	protected $_repoName = 'common';

	/** @inheritdoc */
	protected $_rootFolder = '/var/www/common';

	/** @inheritdoc */
	protected $_composerHome = '';

	/** @inheritdoc */
	protected function _exec($command) {}
}