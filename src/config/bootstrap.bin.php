<?php
/**
 * Фнукицонал, применяющийся в бинарниках
 */

namespace ArtSkillsCommon {
	function includeIfExists($file) {
		if (file_exists($file)) {
			return include_once $file;
		}
		return null;
	}

	if ((!$loader = includeIfExists(__DIR__ . '/../../vendor/autoload.php')) && (!$loader = includeIfExists(__DIR__ . '/../../../../autoload.php'))) {
		$msg = 'You must set up the project dependencies, run the following commands:' . PHP_EOL .
			'curl -sS https://getcomposer.org/installer | php' . PHP_EOL .
			'php composer.phar install' . PHP_EOL;
		fwrite(STDERR, $msg);
		exit(1);
	}

}