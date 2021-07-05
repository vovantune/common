<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\View\Helper;

use ArtSkills\Error\InternalException;
use ArtSkills\Filesystem\Folder;
use ArtSkills\TestSuite\Mock\MethodMocker;
use ArtSkills\View\Helper\AssetHelper;
use ArtSkills\TestSuite\AppTestCase;
use Cake\Http\ServerRequest;
use Cake\View\View;

/**
 * @SuppressWarnings(PHPMD.MethodMix)
 * @SuppressWarnings(PHPMD.MethodProps)
 */
class AssetTest extends AppTestCase
{

    const SCRIPT_VERSION = 123;


    /**
     * helper
     *
     * @var ?AssetHelper
     */
    private ?AssetHelper $_assetHelper = null;

    /**
     * нужные для тестов файлы
     *
     * @var array
     */
    private static $_files = [ // @phpstan-ignore-line
                               'Test' => [
                                   'is_dependent',
                                   'is_dependent2',
                                   'dependency1',
                                   'dependency2',
                                   'dependency3',
                                   'dependency4',
                               ],
                               'TestManual' => [
                                   'file_manual',
                                   'file_auto',
                               ],
    ];

    /**
     * Пустой результат загрузки
     *
     * @var array
     */
    private $_emptyResult = [ // @phpstan-ignore-line
                              AssetHelper::BLOCK_SCRIPT => [],
                              AssetHelper::BLOCK_STYLE => [],
                              AssetHelper::BLOCK_SCRIPT_BOTTOM => [],
    ];

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->_loadHelper();
        parent::setUp();
    }

    /**
     * Грузим хелпер
     *
     * @param array $options
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    private function _loadHelper(array $options = []): void
    {
        $request = new ServerRequest($options);
        $this->_assetHelper = new AssetHelper(new View($request));
        $this->_assetHelper->setAssetVersion(self::SCRIPT_VERSION);
    }

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        foreach (self::$_files as $dirName => $dirFiles) {
            foreach (['js', 'css'] as $type) {
                $fullDirName = WWW_ROOT . $type . DS . $dirName;
                if (!file_exists($fullDirName)) {
                    mkdir($fullDirName);
                }
                foreach ($dirFiles as $fileName) {
                    $fullFileName = $fullDirName . DS . $fileName . '.' . $type;
                    touch($fullFileName);
                }
            }
            // Templates лежат в js
            foreach ($dirFiles as $fileName) {
                $fullFileName = WWW_ROOT . 'js' . DS . $dirName . DS . $fileName . '.hbs';
                file_put_contents(
                    $fullFileName,
                    '<script id="' . $fileName . '" type="text/x-handlebars-template"></script>'
                );
            }
        }
    }

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        foreach (array_keys(self::$_files) as $dirName) {
            foreach (['js', 'css'] as $type) {
                $fullDirName = WWW_ROOT . $type . DS . $dirName;
                $dir = new Folder($fullDirName);
                $dir->delete();
            }
        }
    }

    /**
     * плохой ключ
     */
    public function testBadVarsNameNotString(): void
    {
        $this->expectExceptionMessage("Название переменной должно быть строкой");
        $this->expectException(InternalException::class);
        $this->_assetHelper->setVars([false => 'asd']); // @phpstan-ignore-line
    }

    /** Плохие названия переменных */
    public function testBadVarsInvalidNames(): void
    {
        $invalidNames = [
            '_name',
            '0name',
            'name$',
            'nameщщщ',
        ];

        foreach ($invalidNames as $invalidName) {
            $errorMsg = '';
            try {
                $this->_assetHelper->setVars(
                    [
                        $invalidName => 'value',
                    ]
                );
            } catch (InternalException $e) {
                $errorMsg = $e->getMessage();
            }
            self::assertEquals(
                "Невалидное название переменной '$invalidName'",
                $errorMsg,
                'Не выбросился ексепшн, когда в названии переменной какая-то фигня: ' . $invalidName
            );
        }
    }

    /**
     * не camelCase
     */
    public function testBadVarsSnakeCase(): void
    {
        $this->expectExceptionMessage("Переменная 'camel_case0' не camelCase");
        $this->expectException(InternalException::class);
        $this->_assetHelper->setVars(
            [
                'camel_case0' => 'value',
            ]
        );
    }

    /**
     * не UPPER_CASE
     */
    public function testBadConst(): void
    {
        $this->expectExceptionMessage("Константа 'upper_case0' не UPPER_CASE");
        $this->expectException(InternalException::class);
        $this->_assetHelper->setConsts(
            [
                'upper_case0' => 'value',
            ]
        );
    }


    /** всё ок */
    public function testVarGood(): void
    {
        $this->_assetHelper->setVars(
            [
                'camelCase0' => 'value',
            ]
        );
        $this->_assetHelper->load('test', 'empty');
        $expectedResult = [
            "<script>\n camelCase0 = \"value\";\n</script>",
        ];
        self::assertEquals(
            $expectedResult,
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', [AssetHelper::BLOCK_SCRIPT]),
            'Неправильно сгенерировался скрипт с переменной'
        );

        $this->_assetHelper->setConsts(
            [
                'UPPER_CASE0' => 'value1',
            ]
        );
        $this->_assetHelper->load('test', 'empty');
        $expectedResult = [
            "<script>\n UPPER_CASE0 = \"value1\";\n</script>",
        ];
        self::assertEquals(
            $expectedResult,
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', [AssetHelper::BLOCK_SCRIPT]),
            'Неправильно сгенерировался скрипт с константой'
        );
    }

    /**
     * переменные заданы в 2 прохода, все возможные типы; при втором вызове load старые переменные не добавляются
     */
    public function testVariableValues(): void
    {
        $this->_assetHelper->setVars(
            [
                'test1' => '',
                'test2' => null,
                'test3' => false,
                'test4' => 0,
            ]
        );
        $this->_assetHelper->setVars(
            [
                'test5' => [],
                'test6' => '123.456',
                'test7' => '1 234,00',
                'test8' => 234.567,
            ]
        );
        $this->_assetHelper->load('test', 'empty');
        $expectedResult = [
            "<script>\n test1 = \"\";\n test2 = null;\n test3 = false;\n test4 = 0;\n test5 = [];\n test6 = 123.456;\n test7 = \"1 234,00\";\n test8 = 234.567;\n</script>",
        ];
        self::assertEquals(
            $expectedResult,
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', [AssetHelper::BLOCK_SCRIPT]),
            'Неправильно сгенерировался скрипт с параметрами'
        );

        $this->_assetHelper->load('test', 'empty');
        self::assertEquals(
            [],
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', [AssetHelper::BLOCK_SCRIPT]),
            'Заново вывелись все параметры'
        );
    }

    /**
     * строки с кавычками и переносами
     */
    public function testRiskyStrings(): void
    {
        $this->_assetHelper->setVars(
            [
                'quot' => "asd\"qwe",
                'newLine' => "asd\r\nqwe",
            ]
        );
        $this->_assetHelper->load('test', 'empty');
        $expectedResult = [
            "<script>\n quot = \"asd\\\"qwe\";\n newLine = \"asd\\r\\nqwe\";\n</script>",
        ];
        self::assertEquals(
            $expectedResult,
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', [AssetHelper::BLOCK_SCRIPT]),
            'Неправильно сгенерировались строки с кавычками и переносами'
        );
    }

    /**
     * плохое переопределение
     */
    public function testRewriteNotAllowedAll(): void
    {
        $this->expectExceptionMessage("Не разрешено переопределять test0");
        $this->expectException(InternalException::class);
        $this->_assetHelper->setVars(
            [
                'test0' => 'blabla',
            ]
        );
        $this->_assetHelper->setVars(
            [
                'test0' => 'ololo',
            ]
        );
    }

    /** Перезапись переменных */
    public function testRewriteAllowedAll(): void
    {
        $this->_assetHelper->setVars(
            [
                'test1' => 'ololo',
                'test2' => 'ololo',
            ]
        );
        $this->_assetHelper->setVars(
            [
                'test1' => 'qqq',
                'test2' => 'qqq',
            ],
            true
        );

        $this->_assetHelper->load('test', 'empty');
        $expectedResult = [
            "<script>\n test1 = \"qqq\";\n test2 = \"qqq\";\n</script>",
        ];
        self::assertEquals($expectedResult, MethodMocker::callPrivate($this->_assetHelper, '_getResult', [AssetHelper::BLOCK_SCRIPT]));
    }

    /** Перезапись одной переменной */
    public function testRewriteAllowedOne(): void
    {
        $this->_assetHelper->setVars(
            [
                'test3' => 'пыщьпыщь',
            ]
        );
        $this->_assetHelper->setVars(
            [
                'test3' => 'ololo',
                'test4' => 2,
            ],
            ['test3' => true]
        );

        $this->_assetHelper->load('test', 'empty');
        $expectedResult = [
            "<script>\n test3 = \"ololo\";\n test4 = 2;\n</script>",
        ];
        self::assertEquals($expectedResult, MethodMocker::callPrivate($this->_assetHelper, '_getResult', [AssetHelper::BLOCK_SCRIPT]));
    }

    /**
     * Плохое переопределение
     */
    public function testRewriteNotAllowedOne(): void
    {
        $this->expectExceptionMessage("Не разрешено переопределять test4");
        $this->expectException(InternalException::class);
        $this->_assetHelper->setVars(
            [
                'test3' => 'ololo',
                'test4' => 2,
            ]
        );
        $this->_assetHelper->setVars(
            [
                'test4' => 8,
                'test3' => 'wawawa',
            ],
            ['test3' => true]
        );
    }

    /**
     * Переопределение в другой тип
     */
    public function testRewriteOtherType(): void
    {
        $this->expectExceptionMessage("Попытка переопределить test4 из типа num в string");
        $this->expectException(InternalException::class);
        $this->_assetHelper->setVars(
            [
                'test4' => 2,
            ]
        );
        $this->_assetHelper->setVars(
            [
                'test4' => 'aaa',
            ],
            true
        );
    }

    /** Из null переопределять можно */
    public function testRewriteNull(): void
    {
        $this->_assetHelper->setVars(
            [
                'test5' => null,
            ]
        );
        $this->_assetHelper->setVars(
            [
                'test5' => 'aaa',
            ],
            true
        );

        $this->_assetHelper->load('test', 'empty');
        $expectedResult = [
            "<script>\n test5 = \"aaa\";\n</script>",
        ];
        self::assertEquals($expectedResult, MethodMocker::callPrivate($this->_assetHelper, '_getResult', [AssetHelper::BLOCK_SCRIPT]));
    }

    /**
     * Переопределение после загрузки
     */
    public function testRewriteAfterLoad(): void
    {
        $this->expectExceptionMessage("Не разрешено переопределять test0");
        $this->expectException(InternalException::class);
        $this->_assetHelper->setVars(
            [
                'test0' => 'blabla',
            ]
        );
        $this->_assetHelper->load('test', 'empty');
        $this->_assetHelper->setVars(
            [
                'test0' => 'hehey',
            ]
        );
    }

    /** Добавить ещё переменных после загрузки */
    public function testAddAfterLoad(): void
    {
        $this->_assetHelper->setVars(
            [
                'test0' => 'blabla',
            ]
        );
        $this->_assetHelper->load('test', 'empty');
        MethodMocker::callPrivate($this->_assetHelper, '_getResult', [AssetHelper::BLOCK_SCRIPT]);

        $this->_assetHelper->setVars(
            [
                'test1' => 'уруру',
            ]
        );

        $this->_assetHelper->load('test', 'empty');
        $expectedResult = [
            "<script>\n test1 = \"уруру\";\n</script>",
        ];
        self::assertEquals($expectedResult, MethodMocker::callPrivate($this->_assetHelper, '_getResult', [AssetHelper::BLOCK_SCRIPT]));
    }

    /** пути прописаны вручную */
    public function testAssetManualExists(): void
    {
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'manualExists',
            [
                AssetHelper::KEY_SCRIPT => 'js/TestManual/file_manual.js',
                AssetHelper::KEY_STYLE => 'css/TestManual/file_manual.css',
            ],
        ]);

        $this->_assetHelper->load('test', 'manualExists');
        $expectedResult = [
            AssetHelper::BLOCK_SCRIPT => [
                "\n\t<script src=\"/js/TestManual/file_manual.js?v=" . self::SCRIPT_VERSION . "\"></script>\n",
            ],
            AssetHelper::BLOCK_STYLE => [
                "\n\t<link rel=\"stylesheet\" href=\"/css/TestManual/file_manual.css?v=" . self::SCRIPT_VERSION . "\"/>\n",
            ],
            AssetHelper::BLOCK_SCRIPT_BOTTOM => [],
        ];
        self::assertEquals(
            $expectedResult,
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', []),
            'Неправильно подгрузился скрипт с зависимостями, у которых вручную прописаны пути'
        );
    }

    /**
     * файлов, прописанных вручную, нет
     */
    public function testAssetManualNotExists(): void
    {
        $this->expectExceptionMessage("Прописанного файла js/TestManual/notExists.js не существует");
        $this->expectException(InternalException::class);
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'manualNotExists',
            [
                AssetHelper::KEY_SCRIPT => 'js/TestManual/notExists.js',
                AssetHelper::KEY_STYLE => 'css/TestManual/notExists.css',
            ],
        ]);
        $this->_assetHelper->load('test', 'manualNotExists');
    }

    /** подключение файлов с других сайтов */
    public function testAssetUrl(): void
    {
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'goodUrl',
            [
                AssetHelper::KEY_SCRIPT => [
                    'http://asdf.ru',
                    'https://asdf.ru',
                ],
                AssetHelper::KEY_STYLE => [
                    'http://asdf.ru',
                    'https://asdf.ru',
                ],
            ],
        ]);

        $this->_assetHelper->load('test', 'goodUrl');
        $expectedResult = [
            AssetHelper::BLOCK_SCRIPT => [
                "\n\t<script src=\"http://asdf.ru\"></script>\n\t<script src=\"https://asdf.ru\"></script>\n",
            ],
            AssetHelper::BLOCK_STYLE => [
                "\n\t<link rel=\"stylesheet\" href=\"http://asdf.ru\"/>\n\t<link rel=\"stylesheet\" href=\"https://asdf.ru\"/>\n",
            ],
            AssetHelper::BLOCK_SCRIPT_BOTTOM => [],
        ];
        self::assertEquals(
            $expectedResult,
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', []),
            'Неправильно подгрузился скрипт с зависимостями по урлу'
        );
    }

    /**
     * какой-то плохой урл
     */
    public function testAssetUrlBad(): void
    {
        $this->expectExceptionMessage("Прописанного файла ftp://asdf");
        $this->expectException(InternalException::class);
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'badUrl',
            [
                AssetHelper::KEY_SCRIPT => 'ftp://asdf',
            ],
        ]);

        $this->_assetHelper->load('test', 'badUrl');
    }

    /**
     * прописанный файл на самом деле папка!
     */
    public function testAssetManualDir(): void
    {
        $this->expectExceptionMessage("Прописанного файла js/TestManual не существует");
        $this->expectException(InternalException::class);
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'manualDir',
            [
                AssetHelper::KEY_SCRIPT => ['js/TestManual'],
            ],
        ]);

        $this->_assetHelper->load('test', 'manualDir');
    }

    /** файл выбирается автоматически и его не существует */
    public function testAutoNotExists(): void
    {
        $this->_assetHelper->load('test', 'autoNotExists');
        self::assertEquals($this->_emptyResult, MethodMocker::callPrivate($this->_assetHelper, '_getResult', []), 'Должен быть пустой результат');
    }

    /** файл выбирается автоматически он существует */
    public function testAutoExists(): void
    {
        $this->_assetHelper->load('testManual', 'fileAuto');
        $expectedResult = [
            AssetHelper::BLOCK_SCRIPT => [
                '<script id="file_auto" type="text/x-handlebars-template"></script>',
                '<script src="/js/TestManual/file_auto.js?v=' . self::SCRIPT_VERSION . '"></script>',
            ],
            AssetHelper::BLOCK_STYLE => [
                '<link rel="stylesheet" href="/css/TestManual/file_auto.css?v=' . self::SCRIPT_VERSION . '"/>',
            ],
            AssetHelper::BLOCK_SCRIPT_BOTTOM => [],
        ];
        self::assertEquals(
            $expectedResult,
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', []),
            'Неправильно подгрузился скрипт у которого есть файлы, но его нет в конфиге'
        );
    }

    /** Добавляется префикс */
    public function testUrlPrefix(): void
    {
        $urlPrefix = '/prefix';
        $this->_loadHelper(['webroot' => $urlPrefix]);
        $this->_assetHelper->load('testManual', 'fileAuto');
        $expectedResult = [
            AssetHelper::BLOCK_SCRIPT => [
                '<script id="file_auto" type="text/x-handlebars-template"></script>',
                '<script src="' . $urlPrefix . '/js/TestManual/file_auto.js?v=' . self::SCRIPT_VERSION . '"></script>',
            ],
            AssetHelper::BLOCK_STYLE => [
                '<link rel="stylesheet" href="' . $urlPrefix . '/css/TestManual/file_auto.css?v=' . self::SCRIPT_VERSION . '"/>',
            ],
            AssetHelper::BLOCK_SCRIPT_BOTTOM => [],
        ];
        self::assertEquals(
            $expectedResult,
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', []),
            'Неправильно подгрузился скрипт с префиксом'
        );
    }


    /** загрузка с зависимостями */
    public function testDependencies(): void
    {
        touch(WWW_ROOT . 'js/Test/dependency4.min.js'); // минифицированный скрипт
        touch(WWW_ROOT . 'js/Test/dependency2.min.js'); // минифицированный скрипт

        $this->_loadHelper([
            'params' => [
                'controller' => 'test',
                'action' => 'isDependent',
            ],
        ]);

        MethodMocker::callPrivate($this->_assetHelper, '_setConfigs', [
            [
                'test' => [
                    'isDependent' => [
                        AssetHelper::KEY_DEPEND => [
                            'test.dependency1',
                        ],
                    ],
                    'isDependent2' => [
                        AssetHelper::KEY_IS_BOTTOM => true,
                        AssetHelper::KEY_DEPEND => [
                            'test.dependency1',
                            'test.dependency3',
                        ],
                    ],
                    'dependency1' => [
                        AssetHelper::KEY_DEPEND => [
                            'test.dependency2',
                        ],
                    ],
                    'dependency2' => [
                    ],
                    'dependency3' => [
                    ],
                    'dependency4' => [
                        AssetHelper::KEY_IS_MODULE => true,
                    ],
                ],
            ],
        ]);
        $this->_assetHelper->addDependency('test', 'dependency2');
        $this->_assetHelper->addLayoutDependency('test', 'dependency4');
        $this->_assetHelper->load();
        $expectedResult = [
            AssetHelper::BLOCK_SCRIPT => [
                '<script id="dependency4" type="text/x-handlebars-template"></script>',
                '<script src="/js/Test/dependency4.min.js?v=' . self::SCRIPT_VERSION . '" type="module"></script>',
                '<script id="dependency2" type="text/x-handlebars-template"></script>',
                '<script src="/js/Test/dependency2.min.js?v=' . self::SCRIPT_VERSION . '"></script>',
                '<script id="dependency1" type="text/x-handlebars-template"></script>',
                '<script src="/js/Test/dependency1.js?v=' . self::SCRIPT_VERSION . '"></script>',
                '<script id="is_dependent" type="text/x-handlebars-template"></script>',
                '<script src="/js/Test/is_dependent.js?v=' . self::SCRIPT_VERSION . '"></script>',
            ],
            AssetHelper::BLOCK_STYLE => [
                '<link rel="stylesheet" href="/css/Test/dependency4.css?v=' . self::SCRIPT_VERSION . '"/>',
                '<link rel="stylesheet" href="/css/Test/dependency2.css?v=' . self::SCRIPT_VERSION . '"/>',
                '<link rel="stylesheet" href="/css/Test/dependency1.css?v=' . self::SCRIPT_VERSION . '"/>',
                '<link rel="stylesheet" href="/css/Test/is_dependent.css?v=' . self::SCRIPT_VERSION . '"/>',
            ],
            AssetHelper::BLOCK_SCRIPT_BOTTOM => [],
        ];
        self::assertEquals(
            $expectedResult,
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', []),
            'Неправильно подгрузился скрипт с зависимостями'
        );

        // часть зависимостей уже подгружена
        $this->_assetHelper->load('test', 'isDependent2');
        $expectedResult = [
            AssetHelper::BLOCK_SCRIPT => [
                '<script id="dependency3" type="text/x-handlebars-template"></script>',
                '<script src="/js/Test/dependency3.js?v=' . self::SCRIPT_VERSION . '"></script>',
            ],
            AssetHelper::BLOCK_STYLE => [
                '<link rel="stylesheet" href="/css/Test/dependency3.css?v=' . self::SCRIPT_VERSION . '"/>',
                '<link rel="stylesheet" href="/css/Test/is_dependent2.css?v=' . self::SCRIPT_VERSION . '"/>',
            ],
            AssetHelper::BLOCK_SCRIPT_BOTTOM => [
                '<script id="is_dependent2" type="text/x-handlebars-template"></script>',
                '<script src="/js/Test/is_dependent2.js?v=' . self::SCRIPT_VERSION . '"></script>',
            ],
        ];
        self::assertEquals(
            $expectedResult,
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', []),
            'Неправильно подгрузился скрипт с зависимостями, когда часть зависимостей уже есть'
        );
    }

    /**
     * Зависимость от самого себя
     * Определяется на этапе записи в конфиг
     */
    public function testSelfDependency(): void
    {
        $this->expectExceptionMessage("Зависимость от самого себя test.selfDependent");
        $this->expectException(InternalException::class);
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'selfDependent',
            [
                AssetHelper::KEY_DEPEND => [
                    'test.selfDependent',
                ],
            ],
        ]);
        $this->_assetHelper->load('test', 'selfDependent');
    }

    /**
     * Неявная зависимость от самого себя
     * Определяется при разрешении зависимостей
     */
    public function testCircularDependencies(): void
    {
        $this->expectExceptionMessage("Круговая зависимость у ассета test.selfDependent");
        $this->expectException(InternalException::class);
        MethodMocker::callPrivate($this->_assetHelper, '_setConfigs', [
            [
                'test' => [
                    'selfDependent' => [
                        AssetHelper::KEY_DEPEND => [
                            'test.dep1',
                        ],
                    ],
                    'dep1' => [
                        AssetHelper::KEY_DEPEND => [
                            'test.dep2',
                        ],
                    ],
                    'dep2' => [
                        AssetHelper::KEY_DEPEND => [
                            'test.selfDependent',
                        ],
                    ],
                ],
            ],
        ]);
        $this->_assetHelper->load('test', 'selfDependent');
    }

    /**
     * Конфигурирование уже загруженного ассета
     */
    public function testConfigureLoaded(): void
    {
        $this->expectExceptionMessage("Попытка сконфигурировать ассет testManual.fileAuto, который уже загружен");
        $this->expectException(InternalException::class);
        $this->_assetHelper->load('testManual', 'fileAuto');
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'testManual',
            'fileAuto',
            [
                AssetHelper::KEY_SCRIPT => 'asdf',
            ],
        ]);
    }

    /** добавление переменных, стилей и скриптов полсе того, как был отрисован блок скриптов */
    public function testAddAfterFetchScript(): void
    {
        $this->_assetHelper->setVars(['upperVar' => 'upperValue']);
        $this->_assetHelper->load('testManual', 'fileAuto');
        $this->_assetHelper->setVars(['bottomVar' => 'bottomValue']);
        $this->_assetHelper->fetchScripts();

        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'exceptScript',
            [
                AssetHelper::KEY_STYLE => 'http://asdf',
                AssetHelper::KEY_SCRIPT => 'http://asdf',
                AssetHelper::KEY_IS_BOTTOM => true,
            ],
        ]);
        $this->_assetHelper->setVars(['bottomVar2' => 'bottomValue2']);
        $this->_assetHelper->load('test', 'exceptScript');
        $res = MethodMocker::callPrivate($this->_assetHelper, '_getResult', []);
        // fileAuto добавил стиль и скрипт; setVars добавил переменную в блок скриптов
        // блок скриптов был уже отрисован
        // новые стили нормально добавились в блок стилей
        // новые переменные добавились в нижний блок
        // setVars -> fetch -> load - добавил переменную вниз, т.к. load после fetch
        $expectedResult = [
            AssetHelper::BLOCK_SCRIPT => [
                "<script>\n upperVar = \"upperValue\";\n</script>",
                '<script id="file_auto" type="text/x-handlebars-template"></script>',
                '<script src="/js/TestManual/file_auto.js?v=123"></script>',
            ],
            AssetHelper::BLOCK_STYLE => [
                '<link rel="stylesheet" href="/css/TestManual/file_auto.css?v=123"/>',
                "\n\t<link rel=\"stylesheet\" href=\"http://asdf\"/>\n",
            ],
            AssetHelper::BLOCK_SCRIPT_BOTTOM => [
                "<script>\n bottomVar = \"bottomValue\";\n bottomVar2 = \"bottomValue2\";\n</script>",
                "\n\t<script src=\"http://asdf\"></script>\n",
            ],
        ];
        self::assertEquals($expectedResult, $res);

        // но при попытке добавить ещё что-то в блок скриптов кидается ексепшн
        // потому что этот блок уже был отрисован
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'script',
            [
                AssetHelper::KEY_SCRIPT => 'http://asdf',
            ],
        ]);
        try {
            $this->_assetHelper->load('test', 'script');
            self::fail('Ожидалась ошибка');
        } catch (InternalException $e) {
            self::assertContains('Не могу загрузить ассет test.script: блок script уже выведен', $e->getMessage());
        }
    }

    /** добавление переменных, стилей и скриптов полсе того, как был отрисован блок стилей */
    public function testAddAfterFetchStyle(): void
    {
        $this->_assetHelper->setVars(['upperVar' => 'upperValue']);
        $this->_assetHelper->load('testManual', 'fileAuto');
        $this->_assetHelper->fetchStyles();

        $this->_assetHelper->setVars(['upperVar2' => 'upperValue2']);
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'script',
            [
                AssetHelper::KEY_SCRIPT => 'http://asdf',
            ],
        ]);
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'scriptBottom',
            [
                AssetHelper::KEY_SCRIPT => 'http://asdfg',
                AssetHelper::KEY_IS_BOTTOM => true,
            ],
        ]);
        $this->_assetHelper->load('test', 'script');
        $this->_assetHelper->load('test', 'scriptBottom');
        $res = MethodMocker::callPrivate($this->_assetHelper, '_getResult', []);
        // fileAuto добавил стиль и скрипт
        // блок стилей был уже отрисован
        // новые скрипты и переменные нормально добавились куда надо
        $expectedResult = [
            AssetHelper::BLOCK_SCRIPT => [
                "<script>\n upperVar = \"upperValue\";\n</script>",
                '<script id="file_auto" type="text/x-handlebars-template"></script>',
                '<script src="/js/TestManual/file_auto.js?v=123"></script>',
                "<script>\n upperVar2 = \"upperValue2\";\n</script>",
                "\n\t<script src=\"http://asdf\"></script>\n",
            ],
            AssetHelper::BLOCK_STYLE => [
                '<link rel="stylesheet" href="/css/TestManual/file_auto.css?v=123"/>',
            ],
            AssetHelper::BLOCK_SCRIPT_BOTTOM => [
                "\n\t<script src=\"http://asdfg\"></script>\n",
            ],
        ];
        self::assertEquals($expectedResult, $res);

        // но при попытке добавить ещё что-то в блок скриптов кидается ексепшн
        // потому что этот блок уже был отрисован
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'style',
            [
                AssetHelper::KEY_STYLE => 'http://asdfv',
            ],
        ]);
        try {
            $this->_assetHelper->load('test', 'style');
            self::fail('Ожидалась ошибка');
        } catch (InternalException $e) {
            self::assertContains('Не могу загрузить ассет test.style: блок css уже выведен', $e->getMessage());
        }
    }

    /** добавление переменных, стилей и скриптов полсе того, как был отрисован блок скриптов */
    public function testAddAfterFetchBottom(): void
    {
        $this->_assetHelper->setVars(['upperVar' => 'upperValue']);
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'scriptBottom',
            [
                AssetHelper::KEY_STYLE => 'http://asdfgh',
                AssetHelper::KEY_SCRIPT => 'http://asdf',
                AssetHelper::KEY_IS_BOTTOM => true,
            ],
        ]);
        $this->_assetHelper->load('test', 'scriptBottom');
        $this->_assetHelper->fetchScriptsBottom();

        $this->_assetHelper->setVars(['upperVar2' => 'upperValue2']);
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'exceptBottom',
            [
                AssetHelper::KEY_SCRIPT => 'http://asdfr',
                AssetHelper::KEY_STYLE => 'http://asdfg',
            ],
        ]);
        $this->_assetHelper->load('test', 'exceptBottom');
        $res = MethodMocker::callPrivate($this->_assetHelper, '_getResult', []);
        // добавлен стиль и скрипт снизу
        // нижний блок был уже отрисован
        // новые скрипты и переменные нормально добавились куда надо
        $expectedResult = [
            AssetHelper::BLOCK_SCRIPT => [
                "<script>\n upperVar = \"upperValue\";\n</script>",
                "<script>\n upperVar2 = \"upperValue2\";\n</script>",
                "\n\t<script src=\"http://asdfr\"></script>\n",
            ],
            AssetHelper::BLOCK_STYLE => [
                "\n\t<link rel=\"stylesheet\" href=\"http://asdfgh\"/>\n",
                "\n\t<link rel=\"stylesheet\" href=\"http://asdfg\"/>\n",
            ],
            AssetHelper::BLOCK_SCRIPT_BOTTOM => [
                "\n\t<script src=\"http://asdf\"></script>\n",
            ],
        ];
        self::assertEquals($expectedResult, $res);

        // но при попытке добавить ещё что-то в блок скриптов кидается ексепшн
        // потому что этот блок уже был отрисован
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'newBottom',
            [
                AssetHelper::KEY_SCRIPT => 'http://asdfv',
                AssetHelper::KEY_IS_BOTTOM => true,
            ],
        ]);
        try {
            $this->_assetHelper->load('test', 'newBottom');
            self::fail('Ожидалась ошибка');
        } catch (InternalException $e) {
            self::assertContains('Не могу загрузить ассет test.newBottom: блок scriptBottom уже выведен', $e->getMessage());
        }
    }


    /**
     * Добавление переменных после того, как все блоки были выведены
     */
    public function testAddVarsAfterFetchScripts(): void
    {
        $this->expectExceptionMessage("Все блоки для переменных уже были выведены");
        $this->expectException(InternalException::class);
        $this->_assetHelper->load('testManual', 'fileAuto');
        $this->_assetHelper->fetchScripts();
        $this->_assetHelper->fetchScriptsBottom();
        $this->_assetHelper->setVars(['error' => 'error']);
    }

    /**
     * тесты параметров load
     */
    public function testLoadParams(): void
    {
        $this->_loadHelper([
            'params' => [
                'controller' => 'test',
                'action' => 'fromParams',
            ],
        ]);

        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'fromParams',
            [
                AssetHelper::KEY_VARS => [
                    'testLoad' => AssetHelper::TYPE_STRING,
                ],
            ],
        ]);

        $errorMsg = '';
        try {
            $this->_assetHelper->load();
        } catch (InternalException $e) {
            $errorMsg = $e->getMessage();
        }
        self::assertEquals(
            'Не определены обязательные переменные: testLoad',
            $errorMsg,
            'Не сработала автоматическая подгрузка на основе реквеста'
        );

        $this->_loadHelper([
            'params' => [
                'controller' => 'test',
                'action' => 'empty',
            ],
        ]);
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'fromParams',
            [
                AssetHelper::KEY_VARS => [
                    'testLoad' => AssetHelper::TYPE_STRING,
                ],
            ],
        ]);

        $errorMsg = '';
        try {
            $this->_assetHelper->load();
        } catch (InternalException $e) {
            $errorMsg = $e->getMessage();
        }
        self::assertEquals('', $errorMsg, 'Не сработала автоматическая подгрузка на основе реквеста');

        $this->_loadHelper([
            'params' => [
                'controller' => 'test',
                'action' => 'from_params',
            ],
        ]);
        MethodMocker::callPrivate($this->_assetHelper, '_setActionConfig', [
            'test',
            'fromParams',
            [
                AssetHelper::KEY_VARS => [
                    'testLoad' => AssetHelper::TYPE_STRING,
                ],
            ],
        ]);

        $errorMsg = '';
        try {
            $this->_assetHelper->load();
        } catch (InternalException $e) {
            $errorMsg = $e->getMessage();
        }
        self::assertEquals(
            'Не определены обязательные переменные: testLoad',
            $errorMsg,
            'Параметры не перевелись в camelCase'
        );
    }

    /**
     * тесты на подгрузку списка обязательных переменных из зависимостей, тесты проверок объявления и типов обязательных переменных
     */
    public function testLoadAssetVars(): void
    {
        MethodMocker::callPrivate($this->_assetHelper, '_setConfigs', [
            [
                'test' => [
                    'vars' => [
                        AssetHelper::KEY_VARS => [
                            'var1' => AssetHelper::TYPE_STRING,
                            'var2' => AssetHelper::TYPE_BOOL,
                        ],
                        AssetHelper::KEY_DEPEND => [
                            'test.varsDuplicate',
                        ],
                    ],
                    'varsDuplicate' => [
                        AssetHelper::KEY_VARS => [
                            'var2' => AssetHelper::TYPE_BOOL,
                            'var3' => AssetHelper::TYPE_NUM,
                        ],
                    ],
                    'varsDuplicate2' => [
                        AssetHelper::KEY_VARS => [
                            'var2' => AssetHelper::TYPE_BOOL,
                            'var4' => AssetHelper::TYPE_NUM,
                        ],
                    ],
                    'varsConflict' => [
                        AssetHelper::KEY_VARS => [
                            'var2' => AssetHelper::TYPE_JSON,
                            'var5' => AssetHelper::TYPE_NUM,
                        ],
                    ],
                    'varsUndefined' => [
                        AssetHelper::KEY_VARS => [
                            'var6' => AssetHelper::TYPE_NUM,
                        ],
                    ],
                    'varsWrongType' => [
                        AssetHelper::KEY_VARS => [
                            'var7' => AssetHelper::TYPE_NUM,
                        ],
                    ],
                ],
            ],
        ]);
        $errorMsg = '';
        try {
            $this->_assetHelper->setVars(
                [
                    'var1' => 'asd',
                    'var2' => true,
                    'var3' => 3,
                ]
            );
            $this->_assetHelper->load('test', 'vars');
        } catch (InternalException $e) {
            $errorMsg = $e->getMessage();
        }
        self::assertEquals(
            '',
            $errorMsg,
            'Выбросился ексепшн при попытке загрузить скрипты с одинаковыми переменными с одинаковыми типами'
        );

        $expectedResult = [
            "<script>\n var1 = \"asd\";\n var2 = true;\n var3 = 3;\n</script>",
        ];
        self::assertEquals(
            $expectedResult,
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', [AssetHelper::BLOCK_SCRIPT]),
            'Неправильно сгенерировался скрипт с параметрами'
        );

        $errorMsg = '';
        try {
            $this->_assetHelper->setVars(
                [
                    'var4' => 4,
                ]
            );
            $this->_assetHelper->load('test', 'varsDuplicate2');
        } catch (InternalException $e) {
            $errorMsg = $e->getMessage();
        }
        self::assertEquals(
            '',
            $errorMsg,
            'Выбросился ексепшн при попытке загрузить скрипты с одинаковыми переменными с одинаковыми типами'
        );

        $expectedResult = [
            "<script>\n var4 = 4;\n</script>",
        ];
        self::assertEquals(
            $expectedResult,
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', [AssetHelper::BLOCK_SCRIPT]),
            'Неправильно сгенерировался скрипт с параметрами'
        );


        $errorMsg = '';
        try {
            $this->_assetHelper->load('test', 'varsUndefined');
        } catch (InternalException $e) {
            $errorMsg = $e->getMessage();
        }
        self::assertEquals(
            'Не определены обязательные переменные: var6',
            $errorMsg,
            'Не выбросился ексепшн при попытке загрузить скрипт, когда обязательная переменная не объявлена'
        );

        self::assertEquals(
            [],
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', [AssetHelper::BLOCK_SCRIPT]),
            'Должен быть пустой результат'
        );

        $errorMsg = '';
        try {
            $this->_assetHelper->setVars(
                [
                    'var5' => 5,
                    'var6' => 6,
                ]
            );
            $this->_assetHelper->load('test', 'varsConflict');
        } catch (InternalException $e) {
            $errorMsg = $e->getMessage();
        }
        self::assertEquals(
            'Конфликт переменных: var2 с типами json и bool',
            $errorMsg,
            'Не выбросился ексепшн при попытке загрузить скрипты с одинаковыми переменными с разными типами'
        );

        self::assertEquals(
            [],
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', [AssetHelper::BLOCK_SCRIPT]),
            'Должен быть пустой результат'
        );

        $errorMsg = '';
        try {
            $this->_assetHelper->setVars(
                [
                    'var7' => true,
                ]
            );
            $this->_assetHelper->load('test', 'varsWrongType');
        } catch (InternalException $e) {
            $errorMsg = $e->getMessage();
        }
        self::assertEquals(
            'var7 должна иметь тип num, а не bool',
            $errorMsg,
            'Не выбросился ексепшн при попытке загрузить скрипт с переменной, объявленной не с тем типом'
        );

        self::assertEquals(
            [],
            MethodMocker::callPrivate($this->_assetHelper, '_getResult', [AssetHelper::BLOCK_SCRIPT]),
            'Должен быть пустой результат'
        );
    }
}
