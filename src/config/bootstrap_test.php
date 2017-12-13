<?php
ini_set('soap.wsdl_cache_ttl', 1);

if (!defined('TEST_MODE')) {
	define('TEST_MODE', 1);
}
require_once __DIR__ . '/bootstrap.php';


\ArtSkills\Lib\Env::setHttpClientAdapter(\ArtSkills\TestSuite\HttpClientMock\HttpClientAdapter::class);

use \ArtSkills\Lib\DB;

$testConnection = DB::getConnection(DB::CONNECTION_TEST);
$dbName = $testConnection->config()['database'];
$existingTables = DB::customQuery("SELECT `table_name` FROM `information_schema`.`tables` WHERE `table_schema` = '" . $dbName . "'", DB::CONNECTION_TEST)
	->fetchAll();
if (!empty($existingTables)) {
	$existingTables = '`' . implode('`, `', array_column($existingTables, 0)) . '`';
	DB::customQuery('DROP TABLE ' . $existingTables, DB::CONNECTION_TEST)->closeCursor();
}
unset($testConnection);

\ArtSkills\Lib\AppCache::flushAll();

\ArtSkills\Lib\Env::setFixtureFolder(TEST_FIXTURE);
\ArtSkills\Lib\Env::setMockFolder(TESTS . 'Suite' . DS . 'Mock' . DS);
\ArtSkills\Lib\Env::setMockNamespace('App\Test\Suite\Mock');
