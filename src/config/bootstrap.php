<?php
if (!defined('TEST_FIXTURE')) {
	/**
	 * Папка с файлами фикстур БД
	 */
	define('TEST_FIXTURE', TESTS . 'Fixture' . DS . 'Data' . DS);
}

if (!defined('AS_COMMON')) {
	/**
	 * Путь к текущему коду
	 */
	define('AS_COMMON', ROOT . DS . 'vendor' . DS . 'artskills' . DS . 'common' . DS . 'src' . DS);
}

$tableAliasesDir = APP . 'Model' . DS . 'table_names.php';
if (file_exists($tableAliasesDir)) {
	require_once $tableAliasesDir;
}

require_once __DIR__ . '/functions.php';

\Cake\Database\Type::map('json', \ArtSkills\Database\Type\JsonType::class);
