<?php
ini_set('soap.wsdl_cache_ttl', 1);

const TEST_MODE = 1;

\ArtSkills\Lib\Env::setHttpClientAdapter(\ArtSkills\TestSuite\HttpClientMock\HttpClientAdapter::class);

//$emailTransports = Email

$testConnection = \ArtSkills\Lib\DB::getConnection(\ArtSkills\Lib\DB::CONNECTION_TEST);
$dbName = $testConnection->config()['database'];
$existingTables = $testConnection->query("SELECT `table_name` FROM `information_schema`.`tables` WHERE `table_schema` = '" . $dbName . "'")->fetchAll();
if (!empty($existingTables)) {
	$existingTables = '`' . implode('`, `', array_column($existingTables, 0)) . '`';
	$testConnection->execute('DROP TABLE ' . $existingTables)->closeCursor();
}
unset($testConnection);
