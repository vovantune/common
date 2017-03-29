<?php
namespace ArtSkills\Test\TestCase\View\Helper\AssetTest;

use ArtSkills\Filesystem\Folder;
use ArtSkills\View\Helper\AssetHelper;
use ArtSkills\TestSuite\AppTestCase;
use Cake\Network\Request;
use Cake\View\View;

class AssetTest extends AppTestCase {

	const SCRIPT_VERSION = 123;

	/**
	 * request
	 * @var Request
	 */
	private $_request = null;

	/**
	 * helper
	 * @var AssetHelper
	 */
	private $_assetHelper = null;

	/**
	 * нужные для тестов файлы
	 *
	 * @var array
	 */
	private static $_files = [
		'Test' => [
			'is_dependent',
			'is_dependent2',
			'dependency1',
			'dependency2',
			'dependency3',
		],
		'TestManual' => [
			'file_manual',
			'file_auto',
		],
	];

	/**
	 * Пустой результат загрузки
	 * @var array
	 */
	private $_emptyResult = [
		AssetHelper::BLOCK_SCRIPT => [],
		AssetHelper::BLOCK_STYLE => [],
		AssetHelper::BLOCK_SCRIPT_BOTTOM => [],
	];

	/**
	 * @inheritdoc
	 */
	public function setUp() {
		$this->_request = new Request();
		$this->_assetHelper = new AssetHelper(new View($this->_request));
		$this->_assetHelper->setAssetVersion(self::SCRIPT_VERSION);
		parent::setUp();
	}

	/**
	 * @inheritdoc
	 */
	public static function setUpBeforeClass() {
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
				file_put_contents($fullFileName, '<script id="' . $fileName . '" type="text/x-handlebars-template"></script>');
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		foreach (self::$_files as $dirName => $dirFiles) {
			foreach (['js', 'css'] as $type) {
				$fullDirName = WWW_ROOT . $type . DS . $dirName;
				$dir = new Folder($fullDirName);
				$dir->delete();
			}
		}
	}

	/**
	 * не массив
	 * @expectedException \Exception
	 * @expectedExceptionMessage Переменные должны быть массивом [название => значение]
	 */
	public function testBadVarsNotArray() {
		$this->_assetHelper->setVars('asd');
	}

	/**
	 * плохой ключ
	 * @expectedException \Exception
	 * @expectedExceptionMessage Название переменной должно быть строкой
	 */
	public function testBadVarsNameNotString() {
		$this->_assetHelper->setVars([
			false => 'asd',
		]);
	}

	/** Плохие названия переменных */
	public function testBadVarsInvalidNames() {
		$invalidNames = [
			'_name', '0name', 'name$', 'nameщщщ',
		];

		foreach ($invalidNames as $invalidName) {
			$errorMsg = '';
			try {
				$this->_assetHelper->setVars([
					$invalidName => 'value',
				]);
			} catch (\Exception $e) {
				$errorMsg = $e->getMessage();
			}
			self::assertEquals("Невалидное название переменной '$invalidName'", $errorMsg, 'Не выбросился ексепшн, когда в названии переменной какая-то фигня: ' . $invalidName);
		}
	}

	/**
	 * не camelCase
	 * @expectedException \Exception
	 * @expectedExceptionMessage Переименуйте 'camel_case0' в 'camelCase0'
	 */
	public function testBadVarsSnakeCase() {
		$this->_assetHelper->setVars([
			'camel_case0' => 'value',
		]);
	}

	/** всё ок */
	public function testVarGood() {
		$this->_assetHelper->setVars([
			'camelCase0' => 'value',
		]);
		$this->_assetHelper->load('test', 'empty');
		$expectedResult = [
			"<script>\n camelCase0 = \"value\";\n</script>"
		];
		self::assertEquals($expectedResult, $this->_assetHelper->fetchResult(AssetHelper::BLOCK_SCRIPT), 'Неправильно сгенерировался скрипт с параметрами');
	}

	/**
	 * переменные заданы в 2 прохода, все возможные типы; при втором вызове load старые переменные не добавляются
	 */
	public function testVariableValues() {
		$this->_assetHelper->setVars([
			'test1' => '',
			'test2' => null,
			'test3' => false,
			'test4' => 0,
		]);
		$this->_assetHelper->setVars([
			'test5' => [],
			'test6' => '123.456',
			'test7' => '1 234,00',
			'test8' => 234.567
		]);
		$this->_assetHelper->load('test', 'empty');
		$expectedResult = [
			"<script>\n test1 = \"\";\n test2 = null;\n test3 = false;\n test4 = 0;\n test5 = [];\n test6 = 123.456;\n test7 = \"1 234,00\";\n test8 = 234.567;\n</script>"
		];
		self::assertEquals($expectedResult, $this->_assetHelper->fetchResult(AssetHelper::BLOCK_SCRIPT), 'Неправильно сгенерировался скрипт с параметрами');

		$this->_assetHelper->load('test', 'empty');
		self::assertEquals([], $this->_assetHelper->fetchResult(AssetHelper::BLOCK_SCRIPT), 'Заново вывелись все параметры');
	}

	/**
	 * строки с кавычками и переносами
	 */
	public function testRiskyStrings() {
		$this->_assetHelper->setVars([
			'quot' => "asd\"qwe",
			'newLine' => "asd\r\nqwe",
		]);
		$this->_assetHelper->load('test', 'empty');
		$expectedResult = [
			"<script>\n quot = \"asd\\\"qwe\";\n newLine = \"asd\\r\\nqwe\";\n</script>"
		];
		self::assertEquals($expectedResult, $this->_assetHelper->fetchResult(AssetHelper::BLOCK_SCRIPT), 'Неправильно сгенерировались строки с кавычками и переносами');
	}

	/**
	 * плохое переопределение
	 * @expectedException \Exception
	 * @expectedExceptionMessage Не разрешено переопределять test0
	 */
	public function testRewriteNotAllowedAll() {
		$this->_assetHelper->setVars([
			'test0' => 'blabla',
		]);
		$this->_assetHelper->setVars([
			'test0' => 'ololo',
		]);
	}

	/** Перезапись переменных */
	public function testRewriteAllowedAll() {
		$this->_assetHelper->setVars([
			'test1' => 'ololo',
			'test2' => 'ololo',
		]);
		$this->_assetHelper->setVars([
			'test1' => 'qqq',
			'test2' => 'qqq',
		], true);

		$this->_assetHelper->load('test', 'empty');
		$expectedResult = [
			"<script>\n test1 = \"qqq\";\n test2 = \"qqq\";\n</script>"
		];
		self::assertEquals($expectedResult, $this->_assetHelper->fetchResult(AssetHelper::BLOCK_SCRIPT));
	}

	/** Перезапись одной переменной */
	public function testRewriteAllowedOne() {
		$this->_assetHelper->setVars([
			'test3' => 'пыщьпыщь',
		]);
		$this->_assetHelper->setVars([
			'test3' => 'ololo',
			'test4' => 2,
		], ['test3' => true]);

		$this->_assetHelper->load('test', 'empty');
		$expectedResult = [
			"<script>\n test3 = \"ololo\";\n test4 = 2;\n</script>"
		];
		self::assertEquals($expectedResult, $this->_assetHelper->fetchResult(AssetHelper::BLOCK_SCRIPT));
	}

	/**
	 * Плохое переопределение
	 * @expectedException \Exception
	 * @expectedExceptionMessage Не разрешено переопределять test4
	 */
	public function testRewriteNotAllowedOne() {
		$this->_assetHelper->setVars([
			'test3' => 'ololo',
			'test4' => 2,
		]);
		$this->_assetHelper->setVars([
			'test4' => 8,
			'test3' => 'wawawa',
		], ['test3' => true]);
	}

	/**
	 * Переопределение в другой тип
	 * @expectedException \Exception
	 * @expectedExceptionMessage Попытка переопределить test4 из типа num в string
	 */
	public function testRewriteOtherType() {
		$this->_assetHelper->setVars([
			'test4' => 2,
		]);
		$this->_assetHelper->setVars([
			'test4' => 'aaa',
		], true);
	}

	/** Из null переопределять можно */
	public function testRewriteNull() {
		$this->_assetHelper->setVars([
			'test5' => null,
		]);
		$this->_assetHelper->setVars([
			'test5' => 'aaa',
		], true);

		$this->_assetHelper->load('test', 'empty');
		$expectedResult = [
			"<script>\n test5 = \"aaa\";\n</script>"
		];
		self::assertEquals($expectedResult, $this->_assetHelper->fetchResult(AssetHelper::BLOCK_SCRIPT));
	}

	/**
	 * Переопределение после загрузки
	 * @expectedException \Exception
	 * @expectedExceptionMessage Не разрешено переопределять test0
	 */
	public function testRewriteAfterLoad() {
		$this->_assetHelper->setVars([
			'test0' => 'blabla',
		]);
		$this->_assetHelper->load('test', 'empty');
		$this->_assetHelper->setVars([
			'test0' => 'hehey',
		]);
	}

	/** Добавить ещё переменных после загрузки */
	public function testAddAfterLoad() {
		$this->_assetHelper->setVars([
			'test0' => 'blabla',
		]);
		$this->_assetHelper->load('test', 'empty');
		$this->_assetHelper->fetchResult(AssetHelper::BLOCK_SCRIPT);

		$this->_assetHelper->setVars([
			'test1' => 'уруру',
		]);

		$this->_assetHelper->load('test', 'empty');
		$expectedResult = [
			"<script>\n test1 = \"уруру\";\n</script>"
		];
		self::assertEquals($expectedResult, $this->_assetHelper->fetchResult(AssetHelper::BLOCK_SCRIPT));
	}

	/** пути прописаны вручную */
	public function testAssetManualExists() {
		$this->_assetHelper->setConfig('test', 'manualExists', [
			AssetHelper::KEY_SCRIPT => 'js/TestManual/file_manual.js',
			AssetHelper::KEY_STYLE => 'css/TestManual/file_manual.css',
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
		self::assertEquals($expectedResult, $this->_assetHelper->fetchResult(), 'Неправильно подгрузился скрипт с зависимостями, у которых вручную прописаны пути');
	}

	/**
	 * файлов, прописанных вручную, нет
	 * @expectedException \Exception
	 * @expectedExceptionMessage Прописанного файла js/TestManual/notExists.js не существует
	 */
	public function testAssetManualNotExists() {
		$this->_assetHelper->setConfig('test', 'manualNotExists', [
			AssetHelper::KEY_SCRIPT => 'js/TestManual/notExists.js',
			AssetHelper::KEY_STYLE => 'css/TestManual/notExists.css',
		]);
		$this->_assetHelper->load('test', 'manualNotExists');
	}

	/** файл выбирается автоматически и его не существует */
	public function testAutoNotExists() {
		$this->_assetHelper->load('test', 'autoNotExists');
		self::assertEquals($this->_emptyResult, $this->_assetHelper->fetchResult(), 'Должен быть пустой результат');
	}

	/** файл выбирается автоматически он существует */
	public function testAutoExists() {
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
		self::assertEquals($expectedResult, $this->_assetHelper->fetchResult(), 'Неправильно подгрузился скрипт у которого есть файлы, но его нет в конфиге');
	}

	/** Добавляется префикс */
	public function testUrlPrefix() {
		$urlPrefix = '/prefix';
		$this->_request = new Request(['webroot' => $urlPrefix]);
		$this->_assetHelper = new AssetHelper(new View($this->_request));

		$this->_assetHelper->load('testManual', 'fileAuto');
		$expectedResult = [
			AssetHelper::BLOCK_SCRIPT => [
				'<script id="file_auto" type="text/x-handlebars-template"></script>',
				'<script src="' . $urlPrefix . '/js/TestManual/file_auto.js"></script>',
			],
			AssetHelper::BLOCK_STYLE => [
				'<link rel="stylesheet" href="' . $urlPrefix . '/css/TestManual/file_auto.css"/>',
			],
			AssetHelper::BLOCK_SCRIPT_BOTTOM => [],
		];
		self::assertEquals($expectedResult, $this->_assetHelper->fetchResult(), 'Неправильно подгрузился скрипт с префиксом');
	}



	/** загрузка с зависимостями */
	public function testDependencies() {
		$this->_assetHelper->setConfigs([
			'test' => [
				'isDependent' => [
					AssetHelper::KEY_DEPEND => [
						'test.dependency1',
						'test.dependency2',
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
			],
		]);
		$this->_assetHelper->load('test', 'isDependent');
		$expectedResult = [
			AssetHelper::BLOCK_SCRIPT => [
				'<script id="dependency2" type="text/x-handlebars-template"></script>',
				'<script src="/js/Test/dependency2.js?v=' . self::SCRIPT_VERSION . '"></script>',
				'<script id="dependency1" type="text/x-handlebars-template"></script>',
				'<script src="/js/Test/dependency1.js?v=' . self::SCRIPT_VERSION . '"></script>',
				'<script id="is_dependent" type="text/x-handlebars-template"></script>',
				'<script src="/js/Test/is_dependent.js?v=' . self::SCRIPT_VERSION . '"></script>',
			],
			AssetHelper::BLOCK_STYLE => [
				'<link rel="stylesheet" href="/css/Test/dependency2.css?v=' . self::SCRIPT_VERSION . '"/>',
				'<link rel="stylesheet" href="/css/Test/dependency1.css?v=' . self::SCRIPT_VERSION . '"/>',
				'<link rel="stylesheet" href="/css/Test/is_dependent.css?v=' . self::SCRIPT_VERSION . '"/>',
			],
			AssetHelper::BLOCK_SCRIPT_BOTTOM => [],
		];
		self::assertEquals($expectedResult, $this->_assetHelper->fetchResult(), 'Неправильно подгрузился скрипт с зависимостями');

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
		self::assertEquals($expectedResult, $this->_assetHelper->fetchResult(), 'Неправильно подгрузился скрипт с зависимостями, когда часть зависимостей уже есть');
	}

	/**
	 * Зависимость от самого себя
	 * Определяется на этапе записи в конфиг
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Зависимость от самого себя test.selfDependent
	 */
	public function testSelfDependency() {
		$this->_assetHelper->setConfigs([
			'test' => [
				'selfDependent' => [
					AssetHelper::KEY_DEPEND => [
						'test.selfDependent',
					],
				],
			],
		]);
		$this->_assetHelper->load('test', 'selfDependent');
	}
	/**
	 * Неявная зависимость от самого себя
	 * Определяется при разрешении зависимостей
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Круговая зависимость у ассета test.selfDependent
	 */
	public function testCircularDependencies() {
		$this->_assetHelper->setConfigs([
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
		]);
		$this->_assetHelper->load('test', 'selfDependent');
	}

	/**
	 * тесты параметров load
	 */
	public function testLoadParams() {
		$this->_assetHelper->setConfig('test', 'fromParams', [
			AssetHelper::KEY_VARS => [
				'testLoad' => AssetHelper::TYPE_STRING,
			],
		]);

		$this->_request->addParams(['controller' => 'test', 'action' => 'fromParams']);
		$errorMsg = '';
		try {
			$this->_assetHelper->load();
		} catch (\Exception $e) {
			$errorMsg = $e->getMessage();
		}
		self::assertEquals('Не определены обязательные переменные: testLoad', $errorMsg, 'Не сработала автоматическая подгрузка на основе реквеста');

		$this->_request->addParams(['controller' => 'test', 'action' => 'empty']);
		$errorMsg = '';
		try {
			$this->_assetHelper->load();
		} catch (\Exception $e) {
			$errorMsg = $e->getMessage();
		}
		self::assertEquals('', $errorMsg, 'Не сработала автоматическая подгрузка на основе реквеста');

		$this->_request->addParams(['controller' => 'test', 'action' => 'from_params']);
		$errorMsg = '';
		try {
			$this->_assetHelper->load();
		} catch (\Exception $e) {
			$errorMsg = $e->getMessage();
		}
		self::assertEquals('Не определены обязательные переменные: testLoad', $errorMsg, 'Параметры не перевелись в camelCase');
	}

	/**
	 * тесты на подгрузку списка обязательных переменных из зависиммостей, тесты проверок объявления и типов обязательных переменных
	 */
	public function testLoadAssetVars() {
		$this->_assetHelper->setConfigs([
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
		]);
		$errorMsg = '';
		try {
			$this->_assetHelper->setVars([
				'var1' => 'asd',
				'var2' => true,
				'var3' => 3,
			]);
			$this->_assetHelper->load('test', 'vars');
		} catch (\Exception $e) {
			$errorMsg = $e->getMessage();
		}
		self::assertEquals('', $errorMsg, 'Выбросился ексепшн при попытке загрузить скрипты с одинаковыми переменными с одинаковыми типами');

		$expectedResult = [
			"<script>\n var1 = \"asd\";\n var2 = true;\n var3 = 3;\n</script>"
		];
		self::assertEquals($expectedResult, $this->_assetHelper->fetchResult(AssetHelper::BLOCK_SCRIPT), 'Неправильно сгенерировался скрипт с параметрами');

		$errorMsg = '';
		try {
			$this->_assetHelper->setVars([
				'var4' => 4,
			]);
			$this->_assetHelper->load('test', 'varsDuplicate2');
		} catch (\Exception $e) {
			$errorMsg = $e->getMessage();
		}
		self::assertEquals('', $errorMsg, 'Выбросился ексепшн при попытке загрузить скрипты с одинаковыми переменными с одинаковыми типами');

		$expectedResult = [
			"<script>\n var4 = 4;\n</script>"
		];
		self::assertEquals($expectedResult, $this->_assetHelper->fetchResult(AssetHelper::BLOCK_SCRIPT), 'Неправильно сгенерировался скрипт с параметрами');


		$errorMsg = '';
		try {
			$this->_assetHelper->load('test', 'varsUndefined');
		} catch (\Exception $e) {
			$errorMsg = $e->getMessage();
		}
		self::assertEquals('Не определены обязательные переменные: var6', $errorMsg, 'Не выбросился ексепшн при попытке загрузить скрипт, когда обязательная переменная не объявлена');

		self::assertEquals([], $this->_assetHelper->fetchResult(AssetHelper::BLOCK_SCRIPT), 'Должен быть пустой результат');

		$errorMsg = '';
		try {
			$this->_assetHelper->setVars([
				'var5' => 5,
				'var6' => 6,
			]);
			$this->_assetHelper->load('test', 'varsConflict');
		} catch (\Exception $e) {
			$errorMsg = $e->getMessage();
		}
		self::assertEquals('Конфликт переменных: var2 с типами json и bool', $errorMsg, 'Не выбросился ексепшн при попытке загрузить скрипты с одинаковыми переменными с разными типами');

		self::assertEquals([], $this->_assetHelper->fetchResult(AssetHelper::BLOCK_SCRIPT), 'Должен быть пустой результат');

		$errorMsg = '';
		try {
			$this->_assetHelper->setVars([
				'var7' => true,
			]);
			$this->_assetHelper->load('test', 'varsWrongType');
		} catch (\Exception $e) {
			$errorMsg = $e->getMessage();
		}
		self::assertEquals('var7 должна иметь тип num, а не bool', $errorMsg, 'Не выбросился ексепшн при попытке загрузить скрипт с переменной, объявленной не с тем типом');

		self::assertEquals([], $this->_assetHelper->fetchResult(AssetHelper::BLOCK_SCRIPT), 'Должен быть пустой результат');

	}



}