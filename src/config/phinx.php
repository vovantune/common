<?php
/**
 * Формирование конфига для Phinx на основе конфига приложения
 *
 * @param string $dbConfFile кейковый конфиг с настройками БД
 * @param string $pathsFile файл с константами путей
 * @param string $baseMigrationClass класс, от которого наследовать миграции
 * @return array
 */
function getPhinxConfig($dbConfFile, $pathsFile, $baseMigrationClass = \ArtSkills\Phinx\Migration\AbstractMigration::class) {
	$phinxConfig = [
		'migration_base_class' => $baseMigrationClass,
		'paths' => [
			'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
			'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds',
		],
		'environments' => [
			'default_migration_table' => 'phinxlog',
			'default_database' => 'default',
		],
	];
	require_once($pathsFile);
	$cakeConfig = require_once($dbConfFile);
	$connectionInfo = $cakeConfig['Datasources']['default'];
	$phinxConfig['environments']['default'] = [
		'adapter' => 'mysql',
		'host' => $connectionInfo['host'],
		'name' => $connectionInfo['database'],
		'user' => $connectionInfo['username'],
		'pass' => $connectionInfo['password'],
		'port' => !empty($connectionInfo['port']) ? $connectionInfo['port'] : '3306',
		'charset' => 'utf8',
	];
	return $phinxConfig;
}
