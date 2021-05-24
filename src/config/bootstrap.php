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

if (!defined('CAKE_BIN')) {
    /**
     * Путь до исполняемого файла кейка
     */
    define('CAKE_BIN', ROOT . DS . 'bin' . DS . 'cake');
}


$tableAliasesDir = APP . 'Model' . DS . 'table_names.php';
if (file_exists($tableAliasesDir)) {
    require_once $tableAliasesDir;
}

require_once __DIR__ . '/functions.php';

if (!defined('VERSION_FILE')) {
    define('VERSION_FILE', CONFIG . 'version.txt');
}

if (!defined('CORE_VERSION')) {
    $version = 0;
    if (file_exists(VERSION_FILE)) {
        $version = preg_replace('/\D/', '', file_get_contents(VERSION_FILE));
        if (empty($version)) {
            $version = 0;
        }
    }
    define('CORE_VERSION', $version);
    unset($version);
}


\Cake\Database\Type::map('json', \ArtSkills\Database\Type\JsonType::class);
