<?php

namespace ArtSkills\EntityBuilder;

use ArtSkills\Error\InternalException;
use ArtSkills\Lib\Strings;
use ArtSkills\ORM\Entity;
use ArtSkills\ORM\Query;
use ArtSkills\ORM\Table;
use ArtSkills\Lib\DB;
use ArtSkills\ValueObject\ValueObject;

/**
 * @method $this setConnectionName(string $name)
 * @method $this setModelNamespace(string $namespace)
 * @method $this setModelFolder(string $path)
 * @method $this setTableNamesFile(string $name)
 * @method $this setDescriptionFile(string $name)
 * @method $this setJsTypesFile(string $name)
 * @method $this setBaseTableClass(string $class)
 * @method $this setBaseEntityClass(string $class)
 * @method $this setBaseQueryClass(string $class)
 * @method $this setTemplatesDir(string $path)
 * @method $this setEntityTemplateFile(string $path)
 * @method $this setQueryTemplateFile(string $path)
 * @method $this setTableTemplateFile(string $path)
 */
class EntityBuilderConfig extends ValueObject
{
    /**
     * Соединение, из которого тащим таблицы
     *
     * @var string
     */
    public $connectionName = DB::CONNECTION_DEFAULT;
    /**
     * Неймспейс создаваемых классов
     *
     * @var string
     */
    public $modelNamespace = 'App\Model';
    /**
     * Папка создаваемых классов
     *
     * @var string
     */
    public $modelFolder = '';
    /**
     * Имя файла со списком констант - названий таблиц
     *
     * @var string
     */
    public $tableNamesFile = 'table_names.php';
    /**
     * Имя файла с описанием таблиц
     *
     * @var string
     */
    public $descriptionFile = 'TableList.md';
    /**
     * Имя файла с описанием сущностей для JavaScript
     *
     * @var string
     */
    public $jsTypesFile = 'TableEntityList.js';

    /**
     * Класс, от которого наследовать таблицы
     *
     * @var string
     */
    public $baseTableClass = Table::class;
    /**
     * Класс, от которого наследовать сущности
     *
     * @var string
     */
    public $baseEntityClass = Entity::class;
    /**
     * Класс, от которого наследовать запросы
     *
     * @var string
     */
    public $baseQueryClass = Query::class;

    /**
     * Папка с шаблонами генерируемых файлов
     *
     * @var string
     */
    public $templatesDir = __DIR__ . '/templates/';
    /**
     * Файл шаблона сущности
     *
     * @var string
     */
    public $entityTemplateFile = '';
    /**
     * Файл шаблона запроса
     *
     * @var string
     */
    public $queryTemplateFile = '';
    /**
     * Файл шаблона таблицы
     *
     * @var string
     */
    public $tableTemplateFile = '';

    /** @inheritdoc */
    public function __construct(array $fillValues = [])
    {
        $this->entityTemplateFile = $this->templatesDir . 'Entity.tpl';
        $this->queryTemplateFile = $this->templatesDir . 'Query.tpl';
        $this->tableTemplateFile = $this->templatesDir . 'Table.tpl';
        parent::__construct($fillValues);
    }

    /**
     * Проверить валидность заполнения
     *
     * @throws InternalException
     */
    public function checkValid()
    {
        foreach ($this->_allFieldNames as $fieldName) {
            if (empty($this->{$fieldName})) {
                throw new InternalException("Empty value for field '$fieldName'");
            }
        }
        $trailingDsFields = ['modelFolder', 'templatesDir'];
        foreach ($trailingDsFields as $fieldName) {
            if (!Strings::endsWith($this->$fieldName, '/')) {
                $this->$fieldName = $this->$fieldName . '/';
            }
        }
    }

    /**
     * Прописать себя как конфиг, где нужно
     *
     * @throws InternalException
     */
    public function register()
    {
        EntityBuilder::setConfig($this);
        TableDocumentation::setConfig($this);
    }
}
