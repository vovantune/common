<?php
ini_set('soap.wsdl_cache_ttl', 1);

if (!defined('TEST_MODE')) {
	define('TEST_MODE', 1);
}

\ArtSkills\Lib\Env::setHttpClientAdapter(\ArtSkills\TestSuite\HttpClientMock\HttpClientAdapter::class);

$testConnection = \ArtSkills\Lib\DB::getConnection(\ArtSkills\Lib\DB::CONNECTION_TEST);
$dbName = $testConnection->config()['database'];
$existingTables = $testConnection->query("SELECT `table_name` FROM `information_schema`.`tables` WHERE `table_schema` = '" . $dbName . "'")->fetchAll();
if (!empty($existingTables)) {
	$existingTables = '`' . implode('`, `', array_column($existingTables, 0)) . '`';
	$testConnection->execute('DROP TABLE ' . $existingTables)->closeCursor();
}
unset($testConnection);

\Cake\Cache\Cache::clear(false, '_cake_model_');
\Cake\Cache\Cache::clear(false, '_cake_core_');