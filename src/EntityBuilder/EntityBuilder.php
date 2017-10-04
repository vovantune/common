<?php

namespace ArtSkills\EntityBuilder;

use ArtSkills\Error\Assert;
use ArtSkills\Lib\Arrays;
use ArtSkills\Lib\DB;
use ArtSkills\Lib\Misc;
use ArtSkills\Lib\Strings;
use ArtSkills\TestSuite\Mock\PropertyAccess;
use ArtSkills\Traits\Library;
use ArtSkills\Filesystem\File;
use ArtSkills\Filesystem\Folder;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use DocBlockReader\Reader;

/**
 * Конструктор сущностей для CakePHP
 */
class EntityBuilder
{
	use Library;

	const ENTITY_TEMPLATE_STRING = '{ENTITY}';
	const TIME_CLASS = '\\' . Time::class;

	const FILE_TYPE_TABLE = 'Table';
	const FILE_TYPE_QUERY = 'Query';
	const FILE_TYPE_ENTITY = 'Entity';

	const SCHEMA_TYPE_MAP = [
		'integer' => 'int',
		'biginteger' => 'int',
		'boolean' => 'boolean',
		'float' => 'float',
		'decimal' => 'float',
		'date' => self::TIME_CLASS,
		'time' => self::TIME_CLASS,
		'datetime' => self::TIME_CLASS,
		'timestamp' => self::TIME_CLASS,
		'uuid' => 'string',
		'string' => 'string',
		'text' => 'string',
		'binary' => 'string',
		'json' => 'array',
	];

	/**
	 * Конфиг
	 *
	 * @var EntityBuilderConfig
	 */
	protected static $_config = null;

	/**
	 * Список шаблонов магических методов для таблиц
	 *
	 * @var array
	 */
	protected static $_tableMethods = [];
	/**
	 * Список шаблонов файлов
	 *
	 * @var array
	 */
	protected static $_fileTemplates = [];
	/**
	 * Список базовых классов
	 *
	 * @var array
	 */
	protected static $_baseClasses = [];

	/**
	 * Задать конфиг
	 *
	 * @param EntityBuilderConfig|null $config
	 * @throws \Exception
	 */
	public static function setConfig($config) {
		static::$_config = $config;
		if (empty($config)) {
			return;
		}
		Assert::isInstanceOf($config, EntityBuilderConfig::class, 'Bad config');

		$entityClassName = self::_getClassTemplate(self::FILE_TYPE_ENTITY);
		$queryClassName = self::_getClassTemplate(self::FILE_TYPE_QUERY);
		static::$_tableMethods = [
			'newEntity' => '@method ' . $entityClassName . ' newEntity(array|null $data = null, array $options = [])',
			'newEntities' => '@method ' . $entityClassName . '[] newEntities(array $data, array $options = [])',
			'patchEntity' => '@method ' . $entityClassName . ' patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])',
			'patchEntities' => '@method ' . $entityClassName . '[] patchEntities($entities, array $data, array $options = [])',
			'save' => '@method ' . $entityClassName . '|false save(' . $entityClassName . ' $entity, array|\ArrayAccess $options = [])',
			'saveArr' => '@method ' . $entityClassName . '|false saveArr(array $saveData, ' . $entityClassName . '|null $entity = null, array $options = [])',
			'find' => '@method ' . $queryClassName . ' find(string $type = "all", array|\ArrayAccess $options = [])',
			'get' => '@method ' . $entityClassName . ' get($primaryKey, array|\ArrayAccess $options = [])',
			'getEntity' => '@method ' . $entityClassName . '|false getEntity(' . $entityClassName . '|int $entity, array|\ArrayAccess $options = [])',
			'updateWithLock' => '@method ' . $entityClassName . '|null updateWithLock(' . $queryClassName . '|array $queryData, array $updateData)',
		];
		static::$_fileTemplates = [
			static::FILE_TYPE_TABLE => file_get_contents($config->tableTemplateFile),
			static::FILE_TYPE_QUERY => file_get_contents($config->queryTemplateFile),
			static::FILE_TYPE_ENTITY => file_get_contents($config->entityTemplateFile),
		];
		static::$_baseClasses = [
			static::FILE_TYPE_TABLE => $config->baseTableClass,
			static::FILE_TYPE_QUERY => $config->baseQueryClass,
			static::FILE_TYPE_ENTITY => $config->baseEntityClass,
		];
	}

	/**
	 * Генерим сущности и связи
	 *
	 * @return boolean
	 */
	public static function build() {
		self::_checkConfig();
		TableRegistry::clear();
		$tblList = self::_getTableList();
		$hasChanges = false;
		foreach ($tblList as $tblName) {
			if (self::_buildTableDeps($tblName)) {
				$hasChanges = true;
			}
		}

		$namesUpdated = self::_updateTableNamesFile($tblList);
		if ($namesUpdated) {
			$hasChanges = true;
		}
		TableRegistry::clear();

		return $hasChanges;
	}

	/**
	 * Создаём класс таблицы
	 *
	 * @param string $tableName
	 * @throws \Exception
	 * @return string
	 */
	public static function createTableClass($tableName) {
		self::_checkConfig();
		$tableName = Inflector::underscore($tableName);
		if (!self::_checkTableExists($tableName)) {
			throw new \Exception('Table "' . $tableName . '" does not exist in DB!');
		}

		$entityName = Inflector::camelize(str_replace('`', '', $tableName));
		$tblClassName = $entityName . self::FILE_TYPE_TABLE;
		if (class_exists($tblClassName)) {
			throw new \Exception('Class "' . $tblClassName . '" already exists!');
		}

		$file = self::_getFile($entityName, self::FILE_TYPE_TABLE);
		if ($file->exists()) {
			throw new \Exception('File ' . $file->path . ' already exists!');
		}


		if (!$file->write(self::_processFileTemplate($entityName, self::FILE_TYPE_TABLE))) {
			throw new \Exception("File write error for $entityName; {$file->path}/{$file->name}");
		};
		$file->close();

		return $file->path;
	}

	/**
	 * Методы, для которых нужны комменты
	 *
	 * @param string $tableAlias
	 * @return array
	 */
	protected static function _getRedefineMethods($tableAlias) {
		$methods = static::$_tableMethods;
		$table = self::_getTable($tableAlias);
		if ($table->hasBehavior('Timestamp')) {
			$entityClassName = self::_getClassTemplate(self::FILE_TYPE_ENTITY);
			$methods['touch'] = '@method ' . $entityClassName . ' touch(' . $entityClassName . ' $entity, string $eventName = \'Model.beforeSave\')';
		}
		return $methods;
	}

	/**
	 * Возвращает список алиасов полей
	 *
	 * @param string $entityName
	 * @param array $fields field => comment
	 * @return array alias => field
	 */
	protected static function _getAliases($entityName, $fields) {
		$aliases = [];
		$className = static::$_config->modelNamespace . '\Entity\\' . $entityName;
		if (class_exists($className)) {
			// field => field
			$fields = Arrays::keysFromValues(array_keys($fields));
			$aliases = PropertyAccess::get(new $className, '_aliases');

			foreach ($aliases as $alias => $field) {
				if (empty($fields[$field])) {
					unset($aliases[$alias]);
				}
			}
		}
		return $aliases;
	}

	/**
	 * Формируем список виртуальных полей сущности
	 *
	 * @param string $entityName
	 * @param array $fields field => comment
	 * @return array ['имя поля' => 'тип']
	 */
	private static function _getVirtualFields($entityName, $fields) {
		$virtualFields = [];
		$className = static::$_config->modelNamespace . '\Entity\\' . $entityName;
		if (class_exists($className)) {
			// field => field
			$fields = Arrays::keysFromValues(array_keys($fields));
			$refClass = new \ReflectionClass($className);
			$getPrefix = '_get';
			foreach ($refClass->getMethods(\ReflectionMethod::IS_PROTECTED) as $method) {
				if (Strings::startsWith($method->name, $getPrefix)) {
					$fieldName = lcfirst(Strings::replacePrefix($method->name, $getPrefix));
					if (!empty($fields[$fieldName])) {
						continue;
					}

					$reader = new Reader($refClass->getName(), $method->name);
					$resultType = $reader->getParameter('return');
					if (!$resultType) {
						$resultType = 'mixed';
					} elseif (stristr($resultType, ' ')) {
						$resultType = explode(' ', $resultType)[0];
					}

					$virtualFields[$fieldName] = $resultType;
				}
			}
		}

		return $virtualFields;
	}

	/**
	 * Получить таблицу
	 *
	 * @param string $tableAlias
	 * @return \Cake\ORM\Table
	 */
	protected static function _getTable($tableAlias) {
		if (TableRegistry::exists($tableAlias)) {
			return TableRegistry::get($tableAlias);
		}
		return TableRegistry::get($tableAlias, ['notForceEntity' => true]);
	}

	/**
	 * Файл для класса
	 *
	 * @param string $entityName
	 * @param string $type
	 * @return File
	 */
	private static function _getFile($entityName, $type) {
		return new File(
			static::$_config->modelFolder . $type . '/' . self::_getShortClassName($entityName, $type) . '.php'
		);
	}

	/**
	 * Папка для классов
	 *
	 * @param string $type
	 * @return Folder
	 */
	private static function _getFolder($type) {
		return new Folder(static::$_config->modelFolder . $type);
	}

	/**
	 * Шаблон полного названия класса
	 *
	 * @param string $type
	 * @return string
	 */
	private static function _getClassTemplate($type) {
		return '\\' . static::$_config->modelNamespace . '\\' . $type . '\\' . self::_getShortClassName(
				static::ENTITY_TEMPLATE_STRING, $type
			);
	}

	/**
	 * Неполное название класса
	 *
	 * @param string $entityName
	 * @param string $type
	 * @return string
	 */
	private static function _getShortClassName($entityName, $type) {
		$postfix = ($type == self::FILE_TYPE_ENTITY ? '' : $type);
		return $entityName . $postfix;
	}


	/**
	 * Проверка, что задан конфиг
	 *
	 * @throws \Exception
	 */
	private static function _checkConfig() {
		if (empty(static::$_config)) {
			throw new \Exception('Не задан конфиг');
		}
		static::$_config->checkValid();
	}

	/**
	 * Сгенерировать файл из шаблона
	 *
	 * @param string $entityName
	 * @param string $type
	 * @return string
	 */
	private static function _processFileTemplate($entityName, $type) {
		$search = [
			static::ENTITY_TEMPLATE_STRING,
			'{MODEL_NAMESPACE}',
			'{BASE}',
			'{USE_BASE}',
		];
		$baseClass = static::$_baseClasses[$type];
		list($baseClassNamespace, $baseClassShort) = Misc::namespaceSplit($baseClass);
		if ($baseClassNamespace === (static::$_config->modelNamespace . '\\' . $type)) {
			$useBaseClass = '';
		} else {
			$useBaseClass = "\nuse $baseClass;\n";
		}

		$replace = [
			$entityName,
			static::$_config->modelNamespace,
			$baseClassShort,
			$useBaseClass,
		];

		return str_replace($search, $replace, static::$_fileTemplates[$type]);
	}

	/**
	 * Проверка на существование таблицы
	 *
	 * @param string $tableName
	 * @return boolean
	 */
	private static function _checkTableExists($tableName) {
		$connection = DB::getConnection(static::$_config->connectionName);
		$existingTables = $connection->query(
			"SELECT count(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_schema='" . $connection->config()['database'] . "' AND table_name='" . $tableName . "';"
		)->fetchAll();
		return $existingTables[0][0];
	}

	/**
	 * Формируем список таблиц
	 *
	 * @return array
	 */
	private static function _getTableList() {
		$folder = self::_getFolder(self::FILE_TYPE_TABLE);
		$files = $folder->find('.*Table\.php', true);

		$baseClassFile = Misc::namespaceSplit(static::$_config->baseTableClass, true) . '.php';
		$result = [];
		foreach ($files as $tblFile) {
			if ($tblFile !== $baseClassFile) {
				$result[] = str_replace('.php', '', $tblFile);
			}
		}
		return $result;
	}

	/**
	 * Строим для таблицы все сущности
	 *
	 * @param string $tblName
	 * @return boolean
	 */
	private static function _buildTableDeps($tblName) {
		$refClass = new \ReflectionClass(static::$_config->modelNamespace . '\Table\\' . $tblName);

		if ($refClass->hasProperty('useTable')) {
			return false;
		}

		$classComment = $refClass->getDocComment();
		$entityName = substr($tblName, 0, -5);
		$resultComment = self::_buildTableMethodRedefines(
			$classComment, $entityName, self::_getClassPublicMethods($refClass)
		);

		$hasChanges = false;
		if ($resultComment !== $classComment) {
			self::_writeNewClassComment($refClass, $resultComment);
			$hasChanges = true;
		}

		if (self::_createQueryClass($entityName)) {
			$hasChanges = true;
		}

		if (self::_createEntityClass($entityName)) {
			$hasChanges = true;
		}
		return $hasChanges;
	}

	/**
	 * Добавляем переопределение методов
	 *
	 * @param string $classComment
	 * @param string $entityName
	 * @param string[] $ownMethods
	 * @return string
	 */
	private static function _buildTableMethodRedefines($classComment, $entityName, $ownMethods) {
		if ($classComment !== false) {
			$commArr = explode("\n", $classComment);
		} else {
			$commArr = ['/**', ' */'];
		}
		$addLines = [];
		foreach (static::_getRedefineMethods($entityName) as $tplMethod => $template) {
			if (in_array($tplMethod, $ownMethods)) {
				continue;
			}

			$hasMethod = false;

			$template = str_replace(static::ENTITY_TEMPLATE_STRING, $entityName, $template);
			foreach ($commArr as $commIndex => $commLine) {
				if (stristr($commLine, $template)) {
					$hasMethod = true;
				}

				if (!$hasMethod && preg_match(
						'/\s\*\s\@method\s.+' . $tplMethod . '\(.+/', $commLine
					)
				) { // есть описание такого метода, но не такое, как надо
					$commArr[$commIndex] = ' * ' . $template;
					$hasMethod = true;
				}
			}

			if (!$hasMethod) {
				$addLines[] = ' * ' . $template;
			}
		}

		if (count($addLines)) {
			array_pop($commArr);

			$commArr = array_merge($commArr, $addLines, [" */"]);
			return implode("\n", $commArr);
		} else {
			return implode("\n", $commArr);
		}
	}

	/**
	 * Список публичных методов без наследования
	 *
	 * @param \ReflectionClass $refClass
	 * @return string[]
	 */
	private static function _getClassPublicMethods($refClass) {
		$methods = [];
		foreach ($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
			if ($method->class == $refClass->getName()) {
				$methods[] = $method->name;
			}
		}
		return $methods;
	}

	/**
	 * Записываем новый комментарий для файла
	 *
	 * @param \ReflectionClass $refClass
	 * @param string $newComment
	 */
	private static function _writeNewClassComment($refClass, $newComment) {
		$file = new File($refClass->getFileName());
		$curContent = $file->read();
		if (empty($curContent)) {
			return;
		}
		$oldClassComment = $refClass->getDocComment();
		if ($oldClassComment === false) {
			$curContentArr = explode("\n", $curContent);
			array_splice($curContentArr, $refClass->getStartLine() - 1, 0, explode("\n", $newComment));
			$newContent = implode("\n", $curContentArr);
		} else {
			$newContent = substr_replace(
				$curContent, $newComment, strpos($curContent, $oldClassComment), strlen($oldClassComment)
			);
		}

		$file->write($newContent);
		$file->close();
	}

	/**
	 * Создаём Query класс для каждой таблицы, если это необходимо
	 *
	 * @param string $entityName
	 * @return boolean
	 */
	private static function _createQueryClass($entityName) {
		$file = self::_getFile($entityName, self::FILE_TYPE_QUERY);
		if (!$file->exists()) {
			$file->create();
			$file->write(self::_processFileTemplate($entityName, static::FILE_TYPE_QUERY));
			$file->close();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Создаём Entity сущность, либо обновляем текущую
	 *
	 * @param string $entityName
	 * @return boolean
	 */
	private static function _createEntityClass($entityName) {
		// реальные поля
		$curTblFields = self::_getTableFieldsComments($entityName);

		// алиасы полей
		$aliases = static::_getAliases($entityName, $curTblFields);
		$aliasProperty = "\n";
		foreach ($aliases as $alias => $field) {
			$aliasProperty .= "		'$alias' => '$field',\n";
			$curTblFields[$alias] = str_replace(
					'$' . $field, '$' . $alias, $curTblFields[$field]
				) . " (алиас поля $field)";
		}
		$aliasProperty .= "\t";

		// виртуальные поля (повешены кейковские геттеры)
		$virtualFields = self::_getVirtualFields($entityName, $curTblFields);
		foreach ($virtualFields as $fieldName => $fieldType) {
			$curTblFields[$fieldName] = ' * @property ' . $fieldType . ' $' . $fieldName;
		}

		$tableComment = self::_getTableComment($entityName);
		if (!empty($tableComment)) {
			$curTblFields[] = $tableComment;
		}

		$file = self::_getFile($entityName, self::FILE_TYPE_ENTITY);
		if ($file->exists()) {
			$className = static::$_config->modelNamespace . '\Entity\\' . $entityName;
			$refClass = new \ReflectionClass($className);

			$file = new File($refClass->getFileName());
			$contents = $file->read();
			$contents = preg_replace(
				'/protected \$_aliases = \[[^]]*\];/', 'protected $_aliases = [' . $aliasProperty . '];', $contents
			);
			$file->write($contents);
			$file->close();

			$classComments = $refClass->getDocComment();
			if ($classComments === false) {
				$newComments = implode("\n", array_merge(["/**"], $curTblFields, [" */"]));
				self::_writeNewClassComment($refClass, $newComments);
				return true;
			} else {
				$commentsArr = explode("\n", $classComments);
				$toAddComments = array_diff($curTblFields, $commentsArr);
				$hasChanges = false;
				foreach ($commentsArr as $key => $comm) { // удаляем ненужные свойства
					if ((stristr($comm, '@property') || stristr($comm, '@tableComment')) && !in_array(
							$comm, $curTblFields
						)
					) {
						unset($commentsArr[$key]);
						$hasChanges = true;
					}
				}

				if (count($toAddComments)) {
					array_splice($commentsArr, count($commentsArr) - 1, 0, $toAddComments);
					$hasChanges = true;
				}

				if ($hasChanges) {
					self::_writeNewClassComment($refClass, implode("\n", $commentsArr));
					return true;
				} else {
					return false;
				}
			}
		} else {
			$file->create();
			$template = self::_processFileTemplate($entityName, static::FILE_TYPE_ENTITY);
			$search = ['{PROPERTIES}', '{ALIASES}'];
			$replace = [implode("\n", $curTblFields), $aliasProperty];
			$file->write(str_replace($search, $replace, $template));
			$file->close();
			return true;
		}
	}

	/**
	 * Список полей теблицы
	 *
	 * @param string $entityName
	 * @return array
	 */
	private static function _getTableFieldsComments($entityName) {
		$table = self::_getTable($entityName);
		$tableSchema = $table->getSchema();

		$columnList = $tableSchema->columns();
		$defaultValues = ($tableSchema->defaultValues());

		$result = [];
		foreach ($columnList as $column) {
			$columnInfo = $tableSchema->getColumn($column);
			$result[$column] = ' * @property ' . static::SCHEMA_TYPE_MAP[$columnInfo['type']] . ' $' . $column .
				(array_key_exists($column, $defaultValues) ? ' = ' . var_export($defaultValues[$column], true) : '') .
				(!empty($columnInfo['comment']) ? ' ' . $columnInfo['comment'] : '');
		}

		$associations = $table->associations();
		/** @type \Cake\ORM\Association $assoc */
		foreach ($associations as $assoc) {
			$className = $assoc->className() ? $assoc->className() : $assoc->getName();
			$propertyName = $assoc->getProperty();
			$foreignKeys = $assoc->getForeignKey();
			$bindingKeys = $assoc->getBindingKey();

			$result[$propertyName] = ' * @property ' . $className . (in_array(
					$assoc->type(), [
						'oneToMany',
						'manyToMany',
					]
				) ? '[]' : '') . ' $' . $propertyName . ' `' .
				implode('`, `', (array)$foreignKeys) . '` => `' .
				implode('`, `', (array)$bindingKeys) . '`';
		}

		return $result;
	}

	/**
	 * PHPDoc комментарий к таблице
	 *
	 * @param string $entityName
	 * @return bool|string
	 */
	private static function _getTableComment($entityName) {
		$table = self::_getTable($entityName);

		$connection = DB::getConnection(static::$_config->connectionName);
		$tableName = $table->getTable();
		$tableComment = $connection->query(
			"SELECT table_comment FROM INFORMATION_SCHEMA.TABLES WHERE table_schema='" . $connection->config()['database'] . "' AND table_name='" . $tableName . "';"
		)->fetchAll();
		if (!empty($tableComment) && !empty($tableComment[0][0])) {
			return ' * @tableComment ' . $tableComment[0][0];
		} else {
			return false;
		}
	}

	/**
	 * Формируем PHP файл с константами
	 *
	 * @param string[] $tableList
	 * @return bool
	 */
	private static function _updateTableNamesFile($tableList) {
		$constList = [];
		foreach ($tableList as $className) {
			$entityName = substr($className, 0, -5);
			$table = self::_getTable($entityName);
			$tableName = $table->getTable();
			$tableAlias = $table->getAlias();

			$constList[] = 'const ' . strtoupper($tableName) . ' = "' . $tableAlias . '";';
		}

		$newContent = "<?php\n// This file is autogenerated\n" . implode("\n", $constList);

		$namesFl = new File(static::$_config->modelFolder . '/' . static::$_config->tableNamesFile);
		if ($namesFl->exists()) {
			$curContent = $namesFl->read();
		} else {
			$curContent = '';
		}


		if ($curContent !== $newContent) {
			$namesFl->write($newContent);
			$namesFl->close();
			return true;
		} else {
			return false;
		}
	}

}