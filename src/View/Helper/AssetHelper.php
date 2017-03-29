<?php
namespace ArtSkills\View\Helper;

use ArtSkills\Lib\Env;
use ArtSkills\Lib\Url;
use ArtSkills\Filesystem\File;
use Cake\Utility\Inflector;
use Cake\View\Helper;

class AssetHelper extends Helper
{

	const KEY_SCRIPT = 'script';
	const KEY_STYLE = 'style';
	const KEY_DEPEND = 'depend';
	/** Шаблоны для Handlebars */
	const KEY_TEMPLATE = 'template';
	/** обязательные переменные */
	const KEY_VARS = 'vars';
	/** Скрипт в <head> или внизу <body> */
	const KEY_IS_BOTTOM = 'isBottom';

	const TYPE_NUM = 'num';
	const TYPE_STRING = 'string';
	const TYPE_BOOL = 'bool';
	const TYPE_JSON = 'json';

	const BLOCK_SCRIPT_BOTTOM = 'scriptBottom';
	const BLOCK_SCRIPT = 'script';
	const BLOCK_STYLE = 'css';

	const DEFAULT_PARAMS = [
		'controller' => 'pages',
		'action' => 'index',
	];

	const DEFAULT_PATH_PARTS = [
		self::KEY_STYLE => [
			'folder' => 'css',
			'extension' => 'css',
		],
		self::KEY_SCRIPT => [
			'folder' => 'js',
			'extension' => 'js',
		],
		self::KEY_TEMPLATE => [
			'folder' => 'js',
			'extension' => 'hbs',
		],
	];

	/**
	 * @inheritdoc
	 * формат конфига в AssetHelper.md
	 */
	protected $_defaultConfig = [
		// для ассетов
		'assets' => [
		],
		// для наших либ
		'lib' => [
		],
	];


	/**
	 * Загруженные скрипты/стили
	 *
	 * @var array
	 */
	private $_loadedAssets = [];

	/**
	 * Текущие скрипты/стили
	 *
	 * @var array
	 */
	private $_newAssets = [];

	/**
	 * Ассеты, которые начали обрабатываться, но ещё не закончили
	 * Можно было бы использовать $_newAssets, но тогда нельзя было бы отличить дублирующиеся зависимости от круговых зависимостей
	 *
	 * @var array
	 */
	private $_startedAssets = [];



	/**
	 * Загруженные переменные
	 *
	 * @var array
	 */
	private $_loadedVariables = [];

	/**
	 * Обязательные для текущго набора скриптов переменные
	 *
	 * @var array
	 */
	private $_newVariables = [];


	/**
	 * Объявленные для текущго набора скриптов переменные
	 *
	 * @var array
	 */
	private $_definedVariables = [];

	/**
	 * Параметр для сброса кеша браузера
	 *
	 * @var int
	 */
	private $_assetPostfix = '';


	/**
	 * Результат. Массив тегов по блокам
	 *
	 * @var array
	 */
	private $_result = [
		self::BLOCK_SCRIPT => [],
		self::BLOCK_SCRIPT_BOTTOM => [],
		self::BLOCK_STYLE => [],
	];

	/**
	 * Задать счётчик версий ассетов
	 *
	 * @param int $version
	 * @throws \Exception
	 */
	public function setAssetVersion($version) {
		if (empty($version)) {
			$this->_assetPostfix = '';
		} elseif ($version < 0) {
			throw new \Exception('Невалидная версия ассетов');
		} else {
			$this->_assetPostfix = '?v=' . $version;
		}
	}

	/**
	 * Задать конфиг на 1 экшн
	 * формат конфига в AssetHelper.md
	 *
	 * @param string $controller
	 * @param string $action
	 * @param array $config
	 * @param bool $merge добавить или перезаписать
	 * @throws \Exception
	 */
	public function setConfig($controller, $action, array $config, $merge = true) {
		if (!empty($config[self::KEY_DEPEND])) {
			foreach ($config[self::KEY_DEPEND] as $dependency) {
				if (!strpos($dependency, '.')) {
					throw new \Exception("Неправильный формат задания зависимости $dependency");
				}
				list($dependFolder, $dependFile) = explode('.', $dependency);
				if (
					(Inflector::variable($dependFolder) != $dependFolder)
					|| (Inflector::variable($dependFile) != $dependFile)
				) {
					throw new \Exception("Неправильный формат задания зависимости $dependency");
				}
				if (($dependFolder == $controller) && ($dependFile == $action)) {
					throw new \Exception("Зависимость от самого себя $dependency");
				}
			}
		}
		$this->config("$controller.$action", $config, $merge);
	}

	/**
	 * Задать конфиг на текущий экшн
	 * формат конфига в AssetHelper.md
	 *
	 * @param array $config
	 * @param bool $merge добавить или перезаписать
	 */
	public function setCurrentConfig(array $config, $merge = true) {
		$this->setConfig($this->_getParam(null, 'controller'), $this->_getParam(null, 'action'), $config, $merge);
	}

	/**
	 * Задать зависимость для текущего экшна
	 *
	 * @param string $folder
	 * @param string $file
	 */
	public function addDependency($folder, $file) {
		$this->addDependencies(["$folder.$file"]);
	}

	/**
	 * Задать зависимоси для текущего экшна
	 *
	 * @param string[] $dependencies
	 */
	public function addDependencies(array $dependencies) {
		$this->setCurrentConfig([
			self::KEY_DEPEND => $dependencies,
		]);
	}


	/**
	 * Задать конфиг на несколько экшнов
	 * формат конфига в AssetHelper.md
	 *
	 * @param array $configs
	 * @param bool $merge
	 */
	public function setConfigs(array $configs, $merge = true) {
		foreach ($configs as $controller => $controllerConf) {
			if (strpos($controller, '.') !== false) {
				list($controller, $action) = explode('.', $controller);
				$this->setConfig($controller, $action, $controllerConf, $merge);
			} else {
				foreach ($controllerConf as $action => $actionConf) {
					$this->setConfig($controller, $action, $actionConf, $merge);
				}
			}
		}
	}

	/**
	 * Добавление скриптов и стилей на страницу
	 *
	 * @param null|string $controller по умолчанию из request
	 * @param null|string $action по умолчанию из request
	 * @throws \Exception если была какая-то ошибка
	 */
	public function load($controller = null, $action = null) {
		$controller = $this->_getParam($controller, 'controller');
		$action = $this->_getParam($action, 'action');
		try {
			$this->_loadAsset("$controller.$action");
			$this->_render();
			$this->_finish(true);
		} catch (\Exception $e) {
			$this->_finish(false);
			throw $e;
		}
	}

	/**
	 * Задание значений переменных
	 *
	 * @param array $variables [название => значение]
	 * проставление кавычек строкам и json_encode() массивов сделаются автоматически, передавать сюда такое не нужно!!!
	 * и по названиям переменных пройдутся preg_match и инфлектор, чтоб туда не попадало говно
	 * @param bool|array $overwrite можно ли перезаписать переменные, если они уже определены.
	 * bool сразу для всех, массив - для каждого по отдельности
	 * @throws \Exception если переданы неправильные параметры
	 * или при попытке переопределить переменную, когда это не разрешено
	 */
	public function setVars($variables, $overwrite = false) {
		if (!is_array($variables)) {
			throw new \Exception('Переменные должны быть массивом [название => значение]');
		}
		foreach ($variables as $varName => $varValue) {
			$varName = $this->_validVarName($varName);
			$existingVarType = $this->_existingVarType($varName, true);
			if (empty($existingVarType)) {
				$this->_definedVariables[$varName] = $varValue;
			} else {
				$canOverwrite = (is_array($overwrite) ? !empty($overwrite[$varName]) : $overwrite);
				if (!$canOverwrite) {
					throw new \Exception("Не разрешено переопределять $varName");
				}
				$newVarType = $this->_getVarType($varValue);
				if (empty($existingVarType) || ($existingVarType == $newVarType)) {
					$this->_definedVariables[$varName] = $varValue;
				} else {
					throw new \Exception("Попытка переопределить $varName из типа $existingVarType в $newVarType");
				}
			}
		}
	}

	/**
	 * Вытащить из конфига параметр ассета
	 *
	 * @param string $assetName
	 * @param string $paramName
	 * @return mixed
	 */
	private function _getAssetParam($assetName, $paramName) {
		return $this->config($assetName . '.' . $paramName);
	}

	/**
	 * Возвращает camelCase параметр. Если не задан, то дефолтный
	 *
	 * @param string $value
	 * @param string $name
	 * @return string
	 */
	private function _getParam($value, $name) {
		if (empty($value)) {
			$value = $this->request->param($name);
		}
		if (empty($value)) {
			$value = self::DEFAULT_PARAMS[$name];
		}
		return Inflector::variable($value);
	}

	/**
	 * Загрузка ассета со всеми зависимостями, переменными и проверками
	 *
	 * @param string $assetName
	 * @throws \Exception
	 */
	private function _loadAsset($assetName) {
		if (in_array($assetName, $this->_loadedAssets) || in_array($assetName, $this->_newAssets)) {
			return;
		}
		if (!empty($this->_startedAssets[$assetName])) {
			throw new \Exception("Круговая зависимость у ассета $assetName");
		}
		$this->_startedAssets[$assetName] = true;
		$this->_loadDependencies($assetName);
		unset($this->_startedAssets[$assetName]);
		$this->_loadVariables($assetName);
		$this->_newAssets[] = $assetName;
	}

	/**
	 * Загрузка зависимостей
	 *
	 * @param string $assetName
	 */
	private function _loadDependencies($assetName) {
		$dependencies = $this->_getAssetParam($assetName, self::KEY_DEPEND);
		if (empty($dependencies)) {
			return;
		}
		foreach ($dependencies as $dependency) {
			$this->_loadAsset($dependency);
		}
	}

	/**
	 * Загрузка переменных
	 *
	 * @param string $assetName
	 * @throws \Exception если одна переменная объявлена в нескольких ассетах с разными типами
	 */
	private function _loadVariables($assetName) {
		$variables = $this->_getAssetParam($assetName, self::KEY_VARS);
		if (empty($variables)) {
			return;
		}
		foreach ($variables as $varName => $varType) {
			$existingVarType = $this->_existingVarType($varName, false);
			if (empty($existingVarType)) {
				$this->_newVariables[$varName] = $varType;
			} elseif ($existingVarType != $varType) {
				throw new \Exception("Конфликт переменных: $varName с типами $varType и $existingVarType");
			}
		}
	}

	/**
	 * Если переменная уже объявлена, то возвращает её тип, иначе null
	 *
	 * @param string $varName
	 * @param bool $actual - смотреть формальные или фактические
	 * @return null|string
	 */
	private function _existingVarType($varName, $actual) {
		if (!empty($this->_loadedVariables[$varName])) {
			return $this->_loadedVariables[$varName];
		}
		if ($actual) {
			return (empty($this->_definedVariables[$varName]) ? null : $this->_getVarType($this->_definedVariables[$varName]));
		} else {
			return (empty($this->_newVariables[$varName]) ? null : $this->_newVariables[$varName]);
		}
	}

	/**
	 * Вывод на страницу
	 */
	private function _render() {
		$this->_renderVars();
		$this->_renderAssets();
	}

	/**
	 * Вывод переменных
	 *
	 * @throws \Exception если какие-то переменные не определены или определены неправильно
	 */
	private function _renderVars() {
		$undefinedRequiredVars = array_diff_key($this->_newVariables, $this->_definedVariables);
		if (!empty($undefinedRequiredVars)) {
			throw new \Exception('Не определены обязательные переменные: ' . implode(', ', array_keys($undefinedRequiredVars)));
		}
		if (empty($this->_definedVariables)) {
			return;
		}
		$statements = [];
		foreach ($this->_definedVariables as $varName => $varValue) {
			$expectedType = (empty($this->_newVariables[$varName]) ? null : $this->_newVariables[$varName]);
			$actualType = $this->_getVarType($varValue);
			if (!empty($expectedType) && ($expectedType !== $actualType)) {
				throw new \Exception("$varName должна иметь тип $expectedType, а не $actualType");
			}
			$value = $this->_makeValue($varValue, $actualType);
			$statements[] = "$varName = $value;";
		}
		$html = "<script>\n " . implode("\n ", $statements) . "\n</script>";
		$this->_result[self::BLOCK_SCRIPT][] = $html;
	}

	/**
	 * Формирует значение в соответствии с типом
	 *
	 * @param mixed $value
	 * @param string|null $type
	 * @return string
	 */
	private function _makeValue($value, $type) {
		switch ($type) {
			case self::TYPE_BOOL:
				$value = ($value ? 'true' : 'false');
				break;
			case self::TYPE_NUM:
				// так и остаётся
				break;
			case self::TYPE_STRING:
				// строки энкодятся, чтобы не было проблем с кавычками и переносами строк
			case self::TYPE_JSON:
				$value = json_encode($value, JSON_UNESCAPED_UNICODE);
				break;
			default:
				$value = 'null';
				break;
		}
		return $value;
	}

	/**
	 * Вывод скриптов и стилей
	 */
	private function _renderAssets() {
		foreach ($this->_newAssets as $assetName) {
			$isBottom = $this->_getAssetParam($assetName, self::KEY_IS_BOTTOM);
			$block = (empty($isBottom) ? self::BLOCK_SCRIPT : self::BLOCK_SCRIPT_BOTTOM);

			$templatePath = $this->_getPath($assetName, self::KEY_TEMPLATE, true);
			if (!empty($templatePath)) {
				$file = new File($templatePath);
				$this->_result[$block][] = $file->read();
				$file->close();
			}

			$scriptPath = $this->_getPath($assetName, self::KEY_SCRIPT);
			if (!empty($scriptPath)) {
				$html = $this->_View->Html->script($scriptPath);
				$this->_result[$block][] = $html;
			}

			$stylePath = $this->_getPath($assetName, self::KEY_STYLE);
			if (!empty($stylePath)) {
				$html = $this->_View->Html->css($stylePath);
				$this->_result[self::BLOCK_STYLE][] = $html;
			}
		}
	}

	/**
	 * Возвращает путь к файлу скрипта или стиля
	 *
	 * @param string $assetName
	 * @param string $type скрипт или стиль
	 * @param bool $realPath возвращать uri или путь к файлу
	 * @return string
	 * @throws \Exception если файл явно указан, а его нет
	 */
	private function _getPath($assetName, $type, $realPath = false) {
		$paths = $this->_getAssetParam($assetName, $type);
		if (!empty($paths)) {
			$finalPaths = [];
			foreach ((array)$paths as $path) {
				if (Url::isHttpUrl($path)) {
					$finalPaths[] = $path;
				} else {
					if (!file_exists(WWW_ROOT . $path)) {
						throw new \Exception("Прописанного файла $path не существует");
					}
					$finalPaths[] = '/' . $path . $this->_assetPostfix;
				}
			}
			return $finalPaths;
		}

		$pathParts = self::DEFAULT_PATH_PARTS[$type];
		list($controller, $action) = explode('.', $assetName);
		$fileName = $pathParts['folder'] . '/' . Inflector::camelize($controller) . '/' . Inflector::delimit($action) . '.' . $pathParts['extension'];
		if (file_exists(WWW_ROOT . $fileName)) {
			return $realPath ? realpath(WWW_ROOT . $fileName) : ('/' . $fileName . $this->_assetPostfix);
		}
		return '';
	}

	/**
	 * Проверка, что такое имя можно задать, и приведение его к camelCase
	 *
	 * @param string $varName
	 * @return string
	 * @throws \Exception если имя - не строка или там полнейшее говно
	 */
	private function _validVarName($varName) {
		if (!is_string($varName)) {
			throw new \Exception('Название переменной должно быть строкой');
		}
		if (preg_match('/([^\w\d_]|[а-яё]|^[\d_])/ui', $varName)) {
			throw new \Exception("Невалидное название переменной '$varName'");
		}
		$validName = Inflector::variable($varName);
		if ($validName !== $varName) {
			throw new \Exception("Переименуйте '$varName' в '$validName'");
		}
		return $validName;
	}

	/**
	 * Возвращает строковое название типа переменной. Если тип null, то возвращает null
	 *
	 * @param mixed $value
	 * @return null|string
	 */
	private function _getVarType($value) {
		if (is_null($value)) {
			return null;
		} elseif (is_bool($value)) {
			return self::TYPE_BOOL;
		} elseif (is_numeric($value)) {
			return self::TYPE_NUM;
		} elseif (is_string($value)) {
			return self::TYPE_STRING;
		} else {
			return self::TYPE_JSON;
		}
	}

	/**
	 * Возвращает сгенерированные теги
	 *
	 * @param null|string $block
	 * @return array
	 */
	public function fetchResult($block = null) {
		if (!empty($block)) {
			if (!empty($this->_result[$block])) {
				$result = $this->_result[$block];
				$this->_result[$block] = [];
			} else {
				$result = [];
			}
		} else {
			$result = $this->_result;
			$this->_result = [
				self::BLOCK_SCRIPT => [],
				self::BLOCK_SCRIPT_BOTTOM => [],
				self::BLOCK_STYLE => [],
			];
		}
		return $result;
	}

	/**
	 * Добавление результата и обновление значений свойств
	 *
	 * @param bool $appendResult
	 */
	private function _finish($appendResult) {
		if ($appendResult) {
			if (!Env::isUnitTest()) {
				$result = $this->fetchResult();
				foreach ($result as $block => $tags) {
					foreach ($tags as $tag) {
						$this->_View->append($block, $tag);
					}
				}
			}
			$this->_definedVariables = array_diff_key($this->_definedVariables, $this->_newVariables);
			foreach ($this->_definedVariables as $varName => $value) {
				$this->_loadedVariables[$varName] = $this->_getVarType($value);
			}
			$this->_loadedVariables = array_merge($this->_loadedVariables, $this->_newVariables);
			$this->_loadedAssets = array_merge($this->_loadedAssets, $this->_newAssets);
		} else {
			$this->fetchResult();
		}
		$this->_newAssets = [];
		$this->_newVariables = [];
		$this->_definedVariables = [];
	}
}