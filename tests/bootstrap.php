<?php
$rootFolder = dirname(__DIR__);
require $rootFolder . '/test-app-conf/bootstrap.php';
require $rootFolder . '/src/config/bootstrap_test.php';
\ArtSkills\Lib\Env::setFixtureFolder(__DIR__ . '/Fixture/Data/');
//\ArtSkills\Lib\Env::setMockFolder();
//\ArtSkills\Lib\Env::setMockNamespace();
