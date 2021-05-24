<?php
declare(strict_types=1);

namespace ArtSkills\View\Helper;

use ArtSkills\Error\InternalException;
use ArtSkills\Lib\Env;
use ArtSkills\Lib\Strings;
use ArtSkills\Lib\Url;
use ArtSkills\Filesystem\File;
use Cake\Utility\Inflector;
use Cake\View\Helper;
use Cake\View\View;

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
    /** @var string Скрипт является модулем или нет (type="module") */
    const KEY_IS_MODULE = 'isModule';

    const TYPE_NUM = 'num';
    const TYPE_STRING = 'string';
    const TYPE_BOOL = 'bool';
    const TYPE_JSON = 'json';

    const LAYOUT_POSTFIX = '#layout';

    const BLOCK_SCRIPT_BOTTOM = 'scriptBottom';
    const BLOCK_SCRIPT = 'script';
    const BLOCK_STYLE = 'css';
    const BLOCKS = [
        self::BLOCK_SCRIPT,
        self::BLOCK_SCRIPT_BOTTOM,
        self::BLOCK_STYLE,
    ];

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
    private array $_loadedAssets = [];

    /**
     * Текущие скрипты/стили
     *
     * @var array
     */
    private array $_newAssets = [];

    /**
     * Ассеты, которые начали обрабатываться, но ещё не закончили
     * Можно было бы использовать $_newAssets, но тогда нельзя было бы отличить дублирующиеся зависимости от круговых зависимостей
     *
     * @var array
     */
    private array $_startedAssets = [];


    /**
     * Загруженные переменные
     *
     * @var array
     */
    private array $_loadedVariables = [];

    /**
     * Обязательные для текущго набора скриптов переменные
     *
     * @var array
     */
    private array $_newVariables = [];


    /**
     * Объявленные для текущго набора скриптов переменные
     *
     * @var array
     */
    private array $_definedVariables = [];

    /**
     * Параметр для сброса кеша браузера
     *
     * @var string
     */
    private string $_assetPostfix = '';


    /**
     * Результат. Массив тегов по блокам
     *
     * @var array
     */
    private array $_result = [];

    /**
     * Массив флагов того, был ли уже выведен этот блок
     *
     * @var array
     */
    private array $_blockFetched = [];

    /** @inheritdoc */
    public function __construct(View $View, array $config = [])
    {
        foreach (static::BLOCKS as $block) {
            $this->_result[$block] = [];
            $this->_blockFetched[$block] = false;
        }
        parent::__construct($View, $config);
    }

    /**
     * Задать счётчик версий ассетов
     *
     * @param int $version
     * @throws InternalException
     */
    public function setAssetVersion(int $version)
    {
        if (empty($version)) {
            $this->_assetPostfix = '';
        } elseif ($version < 0) {
            throw new InternalException('Невалидная версия ассетов');
        } else {
            $this->_assetPostfix = '?v=' . $version;
        }
    }

    /**
     * Задать зависимость для текущего экшна
     *
     * @param string $folder
     * @param string $file
     */
    public function addDependency(string $folder, string $file)
    {
        $this->addDependencies(["$folder.$file"]);
    }

    /**
     * Задаём зависимость для layout
     *
     * @param string $folder
     * @param string $file
     */
    public function addLayoutDependency(string $folder, string $file)
    {
        $this->addDependencies(["$folder.$file"], true);
    }


    /**
     * Задать зависимоси для текущего экшна
     *
     * @param string[] $dependencies
     * @param bool $forLayout зависимость layout или события
     */
    public function addDependencies(array $dependencies, bool $forLayout = false)
    {
        $this->_setCurrentConfig([
            self::KEY_DEPEND => $dependencies,
        ], true, $forLayout ? static::LAYOUT_POSTFIX : '');
    }

    /**
     * Закинуть скрипт текущего экшна вниз
     */
    public function setCurrentBottom()
    {
        $this->_setCurrentConfig([self::KEY_IS_BOTTOM => true], true);
    }

    /**
     * Задание значений переменных
     *
     * @param array $variables [название => значение]
     *                         проставление кавычек строкам и json_encode() массивов сделаются автоматически, передавать сюда такое не нужно!!!
     *                         и по названиям переменных пройдутся preg_match и инфлектор, чтоб туда не попадало говно
     * @param bool|array $overwrite можно ли перезаписать переменные, если они уже определены.
     *                              bool сразу для всех, массив - для каждого по отдельности
     * @throws InternalException если переданы неправильные параметры
     * или при попытке переопределить переменную, когда это не разрешено
     */
    public function setVars(array $variables, $overwrite = false)
    {
        $this->_setVars($variables, $overwrite, true);
    }

    /**
     * Задание значений констант
     *
     * @param array $constants [название => значение]
     *                         проставление кавычек строкам и json_encode() массивов сделаются автоматически, передавать сюда такое не нужно!!!
     *                         и по названиям переменных пройдутся preg_match и инфлектор, чтоб туда не попадало говно
     * @param bool|array $overwrite можно ли перезаписать константы, если они уже определены.
     *                              bool сразу для всех, массив - для каждого по отдельности
     * @throws InternalException если переданы неправильные параметры
     * или при попытке переопределить константу, когда это не разрешено
     */
    public function setConsts(array $constants, $overwrite = false)
    {
        $this->_setVars($constants, $overwrite, false);
    }

    /**
     * Вывести блок в шаблон/layout
     *
     * @param string $blockName
     * @return string
     * @throws InternalException
     */
    public function fetchBlock(string $blockName): string
    {
        if (!array_key_exists($blockName, $this->_blockFetched)) {
            throw new InternalException("Неизвестный блок $blockName");
        }
        if ($this->_blockFetched[$blockName]) {
            throw new InternalException("Блок $blockName уже был выведен");
        }
        $this->_blockFetched[$blockName] = true;
        return $this->_View->fetch($blockName);
    }

    /**
     * Вывести скрипты в шаблон
     *
     * @return string
     */
    public function fetchScripts(): string
    {
        return $this->fetchBlock(self::BLOCK_SCRIPT);
    }

    /**
     * Вывести стили в шаблон
     *
     * @return string
     */
    public function fetchStyles(): string
    {
        return $this->fetchBlock(self::BLOCK_STYLE);
    }

    /**
     * Вывести скрипты в шаблон в нижний блок
     *
     * @return string
     */
    public function fetchScriptsBottom(): string
    {
        return $this->fetchBlock(self::BLOCK_SCRIPT_BOTTOM);
    }

    /**
     * Добавление скриптов и стилей на страницу
     *
     * @param null|string $controller по умолчанию из request
     * @param null|string $action по умолчанию из request
     * @throws InternalException если была какая-то ошибка
     */
    public function load(string $controller = null, string $action = null)
    {
        $controller = $this->_getParam($controller, 'controller');
        $action = $this->_getParam($action, 'action');
        try {
            $this->_loadAsset("$controller.$action" . static::LAYOUT_POSTFIX);
            $this->_loadAsset("$controller.$action");
            $this->_render();
            $this->_finish(true);
        } catch (InternalException $e) {
            $this->_finish(false);
            throw $e;
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
     * @throws InternalException
     */
    protected function _setActionConfig(string $controller, string $action, array $config, bool $merge = true)
    {
        $assetName = "$controller.$action";
        if ($this->_isLoaded($assetName)) {
            throw new InternalException("Попытка сконфигурировать ассет $assetName, который уже загружен");
        }
        if (!empty($config[self::KEY_DEPEND])) {
            foreach ($config[self::KEY_DEPEND] as $dependency) {
                if (!strpos($dependency, '.')) {
                    throw new InternalException("Неправильный формат задания зависимости $dependency");
                }
                [$dependFolder, $dependFile] = explode('.', $dependency);
                if ((Inflector::variable($dependFolder) != $dependFolder)
                    || (Inflector::variable($dependFile) != $dependFile)
                ) {
                    throw new InternalException("Неправильный формат задания зависимости $dependency");
                }
                if (($dependFolder == $controller) && ($dependFile == $action)) {
                    throw new InternalException("Зависимость от самого себя $dependency");
                }
            }
        }

        parent::setConfig($assetName, $config, $merge);
    }

    /**
     * Задать конфиг на текущий экшн
     * формат конфига в AssetHelper.md
     *
     * @param array $config
     * @param bool $merge добавить или перезаписать
     * @param string $postfix
     */
    protected function _setCurrentConfig(array $config, bool $merge = true, string $postfix = '')
    {
        $this->_setActionConfig($this->_getParam(null, 'controller'), $this->_getParam(null, 'action') . $postfix, $config, $merge);
    }

    /**
     * Задать конфиг на несколько экшнов
     * формат конфига в AssetHelper.md
     *
     * @param array $configs
     * @param bool $merge
     */
    protected function _setConfigs(array $configs, bool $merge = true)
    {
        foreach ($configs as $controller => $controllerConf) {
            if (strpos($controller, '.') !== false) {
                [$controller, $action] = explode('.', $controller);
                $this->_setActionConfig($controller, $action, $controllerConf, $merge);
            } else {
                foreach ($controllerConf as $action => $actionConf) {
                    $this->_setActionConfig($controller, $action, $actionConf, $merge);
                }
            }
        }
    }

    /**
     * Задание значений переменных или констант.
     * Разница в проверке именований: переменные в camelCase, константы в UPPER_CASE.
     *
     * @param array $variables
     * @param bool|array $overwrite
     * @param bool $isVariable переменная или константа.
     * @throws InternalException
     */
    private function _setVars(array $variables, $overwrite = false, bool $isVariable = true)
    {
        $this->_checkCanRenderVars();
        if (!is_array($variables)) {
            throw new InternalException(($isVariable ? 'Переменные'
                    : 'Константы') . ' должны быть массивом [название => значение]');
        }

        foreach ($variables as $varName => $varValue) {
            $varName = $this->_validVarName($varName, $isVariable);
            $existingVarType = $this->_existingVarType($varName, true);
            if (empty($existingVarType)) {
                $this->_definedVariables[$varName] = $varValue;
            } else {
                $canOverwrite = (is_array($overwrite) ? !empty($overwrite[$varName]) : $overwrite);
                if (!$canOverwrite) {
                    throw new InternalException("Не разрешено переопределять $varName");
                }
                $newVarType = $this->_getVarType($varValue);
                if (empty($existingVarType) || ($existingVarType == $newVarType)) {
                    $this->_definedVariables[$varName] = $varValue;
                } else {
                    throw new InternalException("Попытка переопределить $varName из типа $existingVarType в $newVarType");
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
    private function _getAssetParam(string $assetName, string $paramName)
    {
        return $this->getConfig($assetName . '.' . $paramName);
    }

    /**
     * Возвращает camelCase параметр. Если не задан, то дефолтный
     *
     * @param string $value
     * @param string $name
     * @return string
     */
    private function _getParam(?string $value, string $name): string
    {
        if (empty($value)) {
            $value = $this->getView()->getRequest()->getParam($name);
        }
        if (empty($value)) {
            $value = self::DEFAULT_PARAMS[$name];
        }
        return Inflector::variable($value);
    }

    /**
     * Был ли ассет уже загружен
     *
     * @param string $assetName
     * @return bool
     */
    private function _isLoaded(string $assetName): bool
    {
        return in_array($assetName, $this->_loadedAssets) || in_array($assetName, $this->_newAssets);
    }

    /**
     * Загрузка ассета со всеми зависимостями, переменными и проверками
     *
     * @param string $assetName
     * @throws InternalException
     */
    private function _loadAsset(string $assetName)
    {
        if ($this->_isLoaded($assetName)) {
            return;
        }
        if (!empty($this->_startedAssets[$assetName])) {
            throw new InternalException("Круговая зависимость у ассета $assetName");
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
    private function _loadDependencies(string $assetName)
    {
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
     * @throws InternalException если одна переменная объявлена в нескольких ассетах с разными типами
     */
    private function _loadVariables(string $assetName)
    {
        $variables = $this->_getAssetParam($assetName, self::KEY_VARS);
        if (empty($variables)) {
            return;
        }
        foreach ($variables as $varName => $varType) {
            $existingVarType = $this->_existingVarType($varName, false);
            if (empty($existingVarType)) {
                $this->_newVariables[$varName] = $varType;
            } elseif ($existingVarType != $varType) {
                throw new InternalException("Конфликт переменных: $varName с типами $varType и $existingVarType");
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
    private function _existingVarType(string $varName, bool $actual): ?string
    {
        if (!empty($this->_loadedVariables[$varName])) {
            return $this->_loadedVariables[$varName];
        }
        if ($actual) {
            return (empty($this->_definedVariables[$varName]) ? null
                : $this->_getVarType($this->_definedVariables[$varName]));
        } else {
            return (empty($this->_newVariables[$varName]) ? null : $this->_newVariables[$varName]);
        }
    }

    /**
     * Вывод на страницу
     */
    private function _render()
    {
        $this->_renderVars();
        $this->_renderAssets();
    }

    /**
     * Вывод переменных
     *
     * @throws InternalException если какие-то переменные не определены или определены неправильно
     */
    private function _renderVars()
    {
        $undefinedRequiredVars = array_diff_key($this->_newVariables, $this->_definedVariables);
        if (!empty($undefinedRequiredVars)) {
            throw new InternalException('Не определены обязательные переменные: ' . implode(', ', array_keys($undefinedRequiredVars)));
        }
        if (empty($this->_definedVariables)) {
            return;
        }
        $this->_checkCanRenderVars();
        $statements = [];
        foreach ($this->_definedVariables as $varName => $varValue) {
            $expectedType = (empty($this->_newVariables[$varName]) ? null : $this->_newVariables[$varName]);
            $actualType = $this->_getVarType($varValue);
            if (!empty($expectedType) && ($expectedType !== $actualType)) {
                throw new InternalException("$varName должна иметь тип $expectedType, а не $actualType");
            }
            $value = $this->_makeValue($varValue, $actualType);
            $statements[] = "$varName = $value;";
        }
        $html = "<script>\n " . implode("\n ", $statements) . "\n</script>";
        $this->_result[$this->_getRenderVarsBlock()][] = $html;
    }

    /**
     * Получить блок, в который будут выводиться переменные
     *
     * @return string
     */
    private function _getRenderVarsBlock(): string
    {
        foreach ([self::BLOCK_SCRIPT, self::BLOCK_SCRIPT_BOTTOM] as $block) {
            if (!$this->_blockFetched[$block]) {
                return $block;
            }
        }
        return '';
    }

    /**
     * Проверить, можно ли добавить ещё переменных
     *
     * @throws InternalException
     */
    private function _checkCanRenderVars()
    {
        if (empty($this->_getRenderVarsBlock())) {
            throw new InternalException('Все блоки для переменных уже были выведены');
        }
    }

    /**
     * Формирует значение в соответствии с типом
     *
     * @param mixed $value
     * @param string|null $type
     * @return string
     */
    private function _makeValue($value, ?string $type): string
    {
        switch ($type) {
            case self::TYPE_BOOL:
                $value = ($value ? 'true' : 'false');
                break;
            case self::TYPE_NUM:
                $value = (string)$value;
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
    private function _renderAssets()
    {
        foreach ($this->_newAssets as $assetName) {
            $isBottom = $this->_getAssetParam($assetName, self::KEY_IS_BOTTOM);
            $scriptBlock = (empty($isBottom) ? self::BLOCK_SCRIPT : self::BLOCK_SCRIPT_BOTTOM);

            $templatePath = $this->_getPath($assetName, self::KEY_TEMPLATE, true);
            if (!empty($templatePath)) {
                $this->_checkCanRenderBlock($scriptBlock, $assetName);
                $file = new File($templatePath);
                $this->_result[$scriptBlock][] = $file->read();
                $file->close();
            }

            $scriptPath = $this->_getPath($assetName, self::KEY_SCRIPT);
            if (!empty($scriptPath)) {
                $this->_checkCanRenderBlock($scriptBlock, $assetName);
                if ($this->_getAssetParam($assetName, self::KEY_IS_MODULE)) {
                    $scriptOptions = ['type' => 'module'];
                } else {
                    $scriptOptions = [];
                }
                $html = $this->_View->Html->script($scriptPath, $scriptOptions);
                $this->_result[$scriptBlock][] = $html;
            }

            $stylePath = $this->_getPath($assetName, self::KEY_STYLE);
            if (!empty($stylePath)) {
                $styleBlock = self::BLOCK_STYLE;
                $this->_checkCanRenderBlock($styleBlock, $assetName);
                $html = $this->_View->Html->css($stylePath);
                $this->_result[$styleBlock][] = $html;
            }
        }
    }

    /**
     * Проверить, можно ли добавить что-то в этот блок.
     * Т.е. то, что он ещё не был выведен.
     *
     * @param string $blockName
     * @param string $assetName для сообщения об ошибке
     * @throws InternalException
     */
    private function _checkCanRenderBlock(string $blockName, string $assetName)
    {
        if ($this->_blockFetched[$blockName]) {
            throw new InternalException("Не могу загрузить ассет $assetName: блок $blockName уже выведен");
        }
    }

    /**
     * Возвращает путь к файлу скрипта или стиля
     *
     * @param string $assetName
     * @param string $type скрипт или стиль
     * @param bool $realPath возвращать uri или путь к файлу
     * @return string[]|string|null
     * @throws InternalException если файл явно указан, а его нет
     */
    private function _getPath(string $assetName, string $type, bool $realPath = false)
    {
        $paths = $this->_getAssetParam($assetName, $type);
        if (!empty($paths)) {
            $finalPaths = [];
            foreach ((array)$paths as $path) {
                if (Url::isHttpUrl($path)) {
                    $finalPaths[] = $path;
                } else {
                    // поддержка минифицированного js
                    $path = $this->_getMinifiedFile($path);

                    if (!is_file(WWW_ROOT . $path)) {
                        throw new InternalException("Прописанного файла $path не существует");
                    }

                    $finalPaths[] = '/' . $path . $this->_assetPostfix;
                }
            }
            return $finalPaths;
        }

        $pathParts = self::DEFAULT_PATH_PARTS[$type];
        [$controller, $action] = explode('.', $assetName);


        $fileName = $this->_getMinifiedFile($pathParts['folder'] . '/' . Inflector::camelize($controller) . '/' . $action . '.' . $pathParts['extension']);
        if (is_file(WWW_ROOT . $fileName)) {
            return $realPath ? realpath(WWW_ROOT . $fileName) : ('/' . $fileName . $this->_assetPostfix);
        } else {
            $oldFileName = $this->_getMinifiedFile($pathParts['folder'] . '/' . Inflector::camelize($controller) . '/' . Inflector::delimit($action) . '.' . $pathParts['extension']);
            if (is_file(WWW_ROOT . $oldFileName)) {
                return $realPath ? realpath(WWW_ROOT . $oldFileName) : ('/' . $oldFileName . $this->_assetPostfix);
            } else {
                return null;
            }
        }
    }

    /**
     * Ищем минфицированный файл
     *
     * @param string $path
     * @return string
     */
    private function _getMinifiedFile(string $path): string
    {
        if (Strings::endsWith($path, '.js') && !Strings::endsWith($path, '.min.js')) {
            $minifiedPath = Strings::replacePostfix($path, '.js', '.min.js');
            if (is_file(WWW_ROOT . $minifiedPath)) {
                return $minifiedPath;
            }
        }
        return $path;
    }

    /**
     * Проверка, что такое имя можно задать, и приведение его к camelCase
     *
     * @param mixed $varName
     * @param bool $isVariable
     * @return string
     * @throws InternalException если имя - не строка или там полнейшее говно
     */
    private function _validVarName($varName, bool $isVariable = true): string
    {
        $subjectName = $isVariable ? 'переменной' : 'константы';
        if (!is_string($varName)) {
            throw new InternalException("Название $subjectName должно быть строкой");
        }
        if (preg_match('/([^\w\d_]|[а-яё]|^[\d_])/ui', $varName)) {
            throw new InternalException("Невалидное название $subjectName '$varName'");
        }
        if ($isVariable) {
            $validName = Inflector::variable($varName);
            if ($validName !== $varName) {
                throw new InternalException("Переменная '$varName' не camelCase");
            }
        } else {
            $validName = strtoupper($varName);
            if ($validName !== $varName) {
                throw new InternalException("Константа '$varName' не UPPER_CASE");
            }
        }

        return $validName;
    }

    /**
     * Возвращает строковое название типа переменной. Если тип null, то возвращает null
     *
     * @param mixed $value
     * @return null|string
     */
    private function _getVarType($value): ?string
    {
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
    protected function _getResult(?string $block = null): array
    {
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
    private function _finish(bool $appendResult)
    {
        if ($appendResult) {
            if (!Env::isUnitTest()) {
                $result = $this->_getResult();
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
            $this->_getResult();
        }
        $this->_newAssets = [];
        $this->_newVariables = [];
        $this->_definedVariables = [];
    }
}
