<?php
$rootFolder = dirname(__DIR__);
\Cake\Core\Configure::write('sentryOptions', ['install_default_breadcrumb_handlers' => false]); // для тестов лога в сентри
require $rootFolder . '/test-app-conf/bootstrap.php';
require $rootFolder . '/src/config/bootstrap_test.php';