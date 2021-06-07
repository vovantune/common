<?php
return [
    'serverProtocol' => 'http',
    'serverName' => 'common.artskills.ru',
    'debug' => true,
    'threadsLimit' => 6,

    'App' => [
        'namespace' => 'TestApp',
        'encoding' => env('APP_ENCODING', 'UTF-8'),
        'defaultLocale' => env('APP_DEFAULT_LOCALE', 'en_US'),
        'base' => false,
        'dir' => 'test-app',
        'webroot' => 'webroot',
        'wwwRoot' => WWW_ROOT,
        'fullBaseUrl' => false,
        'imageBaseUrl' => 'img/',
        'cssBaseUrl' => 'css/',
        'jsBaseUrl' => 'js/',
        'paths' => [
            'plugins' => [ROOT . DS . 'plugins' . DS],
            'templates' => [APP . 'Template' . DS],
            'locales' => [APP . 'Locale' . DS],
        ],
    ],

    'Cache' => [
        'default' => [
            'className' => 'File',
            'path' => CACHE,
            'url' => env('CACHE_DEFAULT_URL', null),
        ],

        '_cake_core_' => [
            'className' => 'File',
            'prefix' => 'myapp_cake_core_',
            'path' => CACHE . 'persistent/',
            'serialize' => true,
            'duration' => '+30 seconds',
            'url' => env('CACHE_CAKECORE_URL', null),
        ],

        '_cake_model_' => [
            'className' => 'File',
            'prefix' => 'myapp_cake_model_',
            'path' => CACHE . 'models/',
            'serialize' => true,
            'duration' => '+30 seconds',
            'url' => env('CACHE_CAKEMODEL_URL', null),
        ],

        'short' => [
            'className' => 'File',
            'prefix' => 'short_',
            'path' => CACHE . 'models/',
            'serialize' => true,
            'duration' => '+30 seconds',
            'url' => env('CACHE_CAKEMODEL_URL', null),
        ],
    ],

    'Error' => [
        'errorLevel' => E_ALL,
        'exceptionRenderer' => 'Cake\Error\ExceptionRenderer',
        'skipLog' => [],
        'log' => true,
        'trace' => true,
    ],

    'Email' => [
        'default' => [
            'transport' => 'default',
            'from' => ['nobody@artskills.ru' => 'ArtSkills.ru'],
            'returnPath' => ['gift@artskills.ru' => 'ArtSkills.ru'],
            'replyTo' => ['gift@artskills.ru' => 'ArtSkills.ru'],
            'headers' => ['Precedence' => 'bulk'],
        ],
    ],

    'EmailTransport' => [
        'default' => [
            'className' => 'Mail',
            'host' => 'localhost',
            'port' => 25,
            'timeout' => 30,
            'username' => 'user',
            'password' => 'secret',
            'client' => null,
            'tls' => null,
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
        'test' => [
            'className' => 'ArtSkills.TestEmail',
        ],
    ],

    'Log' => [
        'debug' => [
            'className' => 'ArtSkills.File',
            'path' => LOGS,
            'file' => 'debug',
            'levels' => ['notice', 'info', 'debug'],
            'url' => env('LOG_DEBUG_URL', null),
        ],
        'error' => [
            'className' => 'ArtSkills.File',
            'path' => LOGS,
            'file' => 'error',
            'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
            'url' => env('LOG_ERROR_URL', null),
        ],
        'sentry' => [
            'className' => 'ArtSkills.Sentry',
            'levels' => [],
        ],
    ],
];
