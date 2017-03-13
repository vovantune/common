<?php
namespace ArtSkills\EntityBuilder;

use ArtSkills\Cake\ORM\Entity;
use ArtSkills\Cake\ORM\Query;
use ArtSkills\Cake\ORM\Table;
use ArtSkills\Lib\DB;
use ArtSkills\Lib\Misc;
use ArtSkills\Traits\Singleton;
use ArtSkills\Cake\Filesystem\File;
use ArtSkills\Cake\Filesystem\Folder;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

/**
 * Конструктор сущностей для CakePHP
 */
class EntityBuilder
{
	use Singleton;

	const ENTITY_TEMPLATE_STRING = '{ENTITY}';
	const TIME_CLASS = '\\' . Time::class;
	const TEMPLATES_DIR = __DIR__ . '/templates/';

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
	];

	protected $_connectionName = DB::CONNECTION_DEFAULT;

	protected $_modelNamespace = 'App\Model';
	protected $_modelFolder = '';

	protected $_baseTableClass = Table::class;
	protected $_baseEntityClass = Entity::class;
	protected $_baseQueryClass = Query::class;

	protected $_entityTemplateFile = self::TEMPLATES_DIR . 'Entity.tpl';
	protected $_queryTemplateFile = self::TEMPLATES_DIR . 'Query.tpl';
	protected $_tableTemplateFile = self::TEMPLATES_DIR . 'Table.tpl';

	protected $_tableNamesFile = 'table_names.php';


	private $_tableMethods = [];
	private $_fileTemplates = [];
	private $_baseClasses = [];

	private function __construct() {
		$entityClassName = '\\' . $this->_modelNamespace . '\Entity\\' . self::ENTITY_TEMPLATE_STRING;
		$queryClassName = '\\' . $this->_modelNamespace . '\Query\\' . self::ENTITY_TEMPLATE_STRING . 'Query';
		$this->_tableMethods = [
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
		$this->_fileTemplates = [
			self::FILE_TYPE_TABLE => file_get_contents($this->_tableTemplateFile),
			self::FILE_TYPE_QUERY => file_get_contents($this->_queryTemplateFile),
			self::FILE_TYPE_ENTITY => file_get_contents($this->_entityTemplateFile),
		];
		$this->_baseClasses = [
			self::FILE_TYPE_TABLE => $this->_baseTableClass,
			self::FILE_TYPE_QUERY => $this->_baseQueryClass,
			self::FILE_TYPE_ENTITY => $this->_baseEntityClass,
		];
	}

	/**
	 * Генерим сущности и связи
	 *
	 * @return boolean
	 */
	public function build() {
		$tblList = $this->_getTableList();
		$hasChanges = false;
		foreach ($tblList as $tblName) {
			if ($this->_buildTableDeps($tblName)) {
				$hasChanges = true;
			}
		}

		$namesUpdated = $this->_updateTableNamesFile($tblList);
		if ($namesUpdated) {
			$hasChanges = true;
		}

		return $hasChanges;
	}

	/**
	 * Создаём класс таблицы
	 *
	 * @param string $tableName
	 * @throws \Exception
	 */
	public function createTableClass($tableName) {
		$tableName = Inflector::underscore($tableName);
		if (!$this->_checkTableExists($tableName)) {
			throw new \Exception('Table "' . $tableName . '" not exists in DB!');
		}

		$entityName = Inflector::camelize(str_replace('`', '', $tableName));
		$tblClassName = $entityName . 'Table';
		if (class_exists($tblClassName)) {
			throw new \Exception('Class "' . $tblClassName . '" already exists!');
		}

		$file = new File($this->_modelFolder . '/Table/' . $tblClassName . '.php');
		if ($file->exists()) {
			throw new \Exception('File ' . $file->path . ' already exists!');
		}


		$file->write($this->_processFileTemplate($entityName, self::FILE_TYPE_TABLE));
		$file->close();
	}

	/**
	 * Сгенерировать файл из шаблона
	 *
	 * @param string $entityName
	 * @param string $type
	 * @return string
	 */
	protected function _processFileTemplate($entityName, $type) {
		$search = [
			self::ENTITY_TEMPLATE_STRING,
			'{MODEL_NAMESPACE}',
			'{BASE}',
			'{USE_BASE}',
		];
		$baseClass = $this->_baseClasses[$type];
		list($baseClassNamespace, $baseClassShort) = Misc::namespaceSplit($baseClass);
		if ($baseClassNamespace === ($this->_modelNamespace . '\\' . $type)) {
			$useBaseClass = '';
		} else {
			$useBaseClass = "\nuse $baseClass;\n";
		}

		$replace = [
			$entityName,
			$this->_modelNamespace,
			$baseClassShort,
			$useBaseClass
		];

		return str_replace($search, $replace, $this->_fileTemplates[$type]);
	}

	/**
	 * Проверка на существование таблицы
	 *
	 * @param string $tableName
	 * @return boolean
	 */
	private function _checkTableExists($tableName) {
		$connection = DB::getConnection($this->_connectionName);
		$existingTables = $connection->query("SELECT count(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_schema='" . $connection->config()['database'] . "' AND table_name='" . $tableName . "';")->fetchAll();
		return $existingTables[0][0];
	}

	/**
	 * Формируем список таблиц
	 *
	 * @return array
	 */
	private function _getTableList() {
		$folder = new Folder($this->_modelFolder . '/Table');
		$files = $folder->find('.*Table\.php', true);

		$baseClassFile = Misc::namespaceSplit($this->_baseTableClass, true) . '.php';
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
	private function _buildTableDeps($tblName) {
		$refClass = new \ReflectionClass($this->_modelNamespace . '\Table\\' . $tblName);

		$classComment = $refClass->getDocComment();
		$entityName = substr($tblName, 0, -5);
		$resultComment = $this->_buildTableMethodRedefines($classComment, $entityName, $this->_getClassPublicMethods($refClass));

		$hasChanges = false;
		if ($resultComment !== $classComment) {
			$this->_writeNewClassComment($refClass, $resultComment);
			$hasChanges = true;
		}

		if ($this->_createQueryClass($entityName)) {
			$hasChanges = true;
		}

		if ($this->_createEntityClass($entityName)) {
			$hasChanges = true;
		}
		return $hasChanges;
	}

	/**
	 * Методы, для которых нужны комменты
	 *
	 * @param string $tableAlias
	 * @return array
	 */
	protected function _getRedefineMethods($tableAlias) {
		$methods = $this->_tableMethods;
		$table = TableRegistry::get($tableAlias);
		if ($table->hasBehavior('Timestamp')) {
			$entityClassName = '\\' . $this->_modelNamespace . '\Entity\\' . self::ENTITY_TEMPLATE_STRING;
			$methods['touch'] = '@method ' . $entityClassName . ' touch(' . $entityClassName . ' $entity, string $eventName = \'Model.beforeSave\')';
		}
		return $methods;
	}

	/**
	 * Добавляем переопределение методов
	 *
	 * @param string $classComment
	 * @param string $entityName
	 * @param string[] $ownMethods
	 * @return string
	 */
	private function _buildTableMethodRedefines($classComment, $entityName, $ownMethods) {
		if ($classComment !== false) {
			$commArr = explode("\n", $classComment);
		} else {
			$commArr = ['/**', ' */'];
		}
		$addLines = [];
		foreach ($this->_getRedefineMethods($entityName) as $tplMethod => $template) {
			if (in_array($tplMethod, $ownMethods)) {
				continue;
			}

			$hasMethod = false;

			$template = str_replace(self::ENTITY_TEMPLATE_STRING, $entityName, $template);
			foreach ($commArr as $commIndex => $commLine) {
				if (stristr($commLine, $template)) {
					$hasMethod = true;
				}

				if (!$hasMethod && preg_match('/\s\*\s\@method\s.+' . $tplMethod . '\(.+/', $commLine)) { // есть описание такого метода, но не такое, как надо
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
	private function _getClassPublicMethods($refClass) {
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
	private function _writeNewClassComment($refClass, $newComment) {
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
			$newContent = substr_replace($curContent, $newComment, strpos($curContent, $oldClassComment), strlen($oldClassComment));
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
	private function _createQueryClass($entityName) {
		$queryClassName = $entityName . 'Query';
		$file = new File($this->_modelFolder . '/Query/' . $queryClassName . '.php');
		if (!$file->exists()) {
			$file->create();
			$file->write($this->_processFileTemplate($entityName, self::FILE_TYPE_QUERY));
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
	private function _createEntityClass($entityName) {
		$curTblFields = $this->_getTableFieldsComments($entityName);
		$tableComment = $this->_getTableComment($entityName);
		$aliases = $this->_getAliases($entityName);
		if (!empty($tableComment)) {
			$curTblFields[] = $tableComment;
		}
		$aliasProperty = "\n";
		foreach ($aliases as $alias => $field) {
			$aliasProperty .= "		'$alias' => '$field',\n";
			$curTblFields[$alias] = str_replace('$' . $field, '$' . $alias, $curTblFields[$field]) . " (алиас поля $field)";
		}
		$aliasProperty .= "\t";

		$file = new File($this->_modelFolder . '/Entity/' . $entityName . '.php');
		if ($file->exists()) {
			$className = $this->_modelNamespace . '\Entity\\' . $entityName;
			$refClass = new \ReflectionClass($className);

			$file = new File($refClass->getFileName());
			$contents = $file->read();
			$contents = preg_replace('/protected \$_aliases = \[[^]]*\];/', 'protected $_aliases = [' . $aliasProperty . '];', $contents);
			$file->write($contents);
			$file->close();

			$classComments = $refClass->getDocComment();
			if ($classComments === false) {
				$newComments = implode("\n", array_merge(["/**"], $curTblFields, [" */"]));
				$this->_writeNewClassComment($refClass, $newComments);
				return true;
			} else {
				$commentsArr = explode("\n", $classComments);
				$toAddComments = array_diff($curTblFields, $commentsArr);
				$hasChanges = false;
				foreach ($commentsArr as $key => $comm) { // удаляем ненужные свойства
					if ((stristr($comm, '@property') || stristr($comm, '@tableComment')) && !in_array($comm, $curTblFields)) {
						unset($commentsArr[$key]);
						$hasChanges = true;
					}
				}

				if (count($toAddComments)) {
					array_splice($commentsArr, count($commentsArr) - 1, 0, $toAddComments);
					$hasChanges = true;
				}

				if ($hasChanges) {
					$this->_writeNewClassComment($refClass, implode("\n", $commentsArr));
					return true;
				} else {
					return false;
				}
			}
		} else {
			$file->create();
			$template = $this->_processFileTemplate($entityName, self::FILE_TYPE_ENTITY);
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
	private function _getTableFieldsComments($entityName) {
		$table = TableRegistry::get($entityName);
		$tableSchema = $table->schema();

		$columnList = $tableSchema->columns();
		$defaultValues = ($tableSchema->defaultValues());

		$result = [];
		foreach ($columnList as $column) {
			$columnInfo = $tableSchema->column($column);
			$result[$column] = ' * @property ' . self::SCHEMA_TYPE_MAP[$columnInfo['type']] . ' $' . $column .
				(array_key_exists($column, $defaultValues) ? ' = ' . var_export($defaultValues[$column], true) : '') .
				(!empty($columnInfo['comment']) ? ' ' . $columnInfo['comment'] : '');
		}

		$associations = $table->associations();
		/** @type \Cake\ORM\Association $assoc */
		foreach ($associations as $assoc) {
			$className = $assoc->className() ? $assoc->className() : $assoc->name();
			$result[] = ' * @property ' . $className . (in_array($assoc->type(), [
					'oneToMany',
					'manyToMany',
				]) ? '[]' : '') . ' $' . $assoc->property() . ' `' .
				implode('`, `', (array)$assoc->foreignKey()) . '` => `' .
				implode('`, `', (array)$assoc->bindingKey()) . '`';
		}

		return $result;
	}

	/**
	 * PHPDoc комментарий к таблице
	 *
	 * @param string $entityName
	 * @return bool|string
	 */
	private function _getTableComment($entityName) {
		$table = TableRegistry::get($entityName);

		$connection = DB::getConnection($this->_connectionName);
		$tableComment = $connection->query("SELECT table_comment FROM INFORMATION_SCHEMA.TABLES WHERE table_schema='" . $connection->config()['database'] . "' AND table_name='" . $table->table() . "';")->fetchAll();
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
	private function _updateTableNamesFile($tableList) {
		$constList = [];
		foreach ($tableList as $className) {
			$entityName = substr($className, 0, -5);
			$table = TableRegistry::get($entityName);
			$constList[] = 'const ' . strtoupper($table->table()) . ' = "' . $table->alias() . '";';
		}

		$newContent = "<?php\n// This file is autogenerated\n" . implode("\n", $constList);

		$namesFl = new File($this->_modelFolder . '/' . $this->_tableNamesFile);
		$curContent = $namesFl->read();

		if ($curContent !== $newContent) {
			$namesFl->write($newContent);
			$namesFl->close();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Возвращает список алиасов полей cf_
	 *
	 * @param string $entityName
	 * @return array
	 */
	protected function _getAliases($entityName) {
		return [];
	}
}