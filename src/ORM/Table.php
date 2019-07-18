<?php

namespace ArtSkills\ORM;

use ArtSkills\Lib\Env;
use ArtSkills\Lib\Strings;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;

class Table extends \Cake\ORM\Table
{

	/**
	 * @inheritdoc
	 * прописывание правильной сущности
	 */
	public function initialize(array $config)
	{
		if (!Env::isUnitTest() || !empty($config['testInit'])) {
			// в юнит тестах иногда инициализируются классы таблиц при том, что работы с таблицей в базе не происходит
			// и в таких случаях фикстуры обычно не объявлены и таблица в базе не создаётся
			// а _initTimeStampBehavior вызывает получение списка полей, для которого таблица обязательна
			// поэтому добавил проверку
			$this->_initTimeStampBehavior();
		}
		if (empty($config['notForceEntity'])) {
			// для построителя сущностей
			// когда он запускается, то сущности может не быть
			// он ведь и нужен, чтоб её создать
			$this->setEntityClass(self::_getAlias());
		}
		parent::initialize($config);
	}

	/**
	 * Обёртка для TableRegistry::get() для автодополнения
	 *
	 * @return static
	 */
	public static function instance()
	{
		return TableRegistry::getTableLocator()->get(static::_getAlias());
	}

	/**
	 * Сохранение массивов, чтоб в одну строчку
	 *
	 * @param array $saveData
	 * @param Entity|null|int $entity null для новой записи, сущность или её id для редактирования
	 * @param array $options
	 * @return bool|Entity
	 */
	public function saveArr($saveData, $entity = null, $options = [])
	{
		if (empty($entity)) {
			$entity = $this->newEntity();
		} else {
			$entity = $this->getEntity($entity);
			if (empty($entity)) {
				return false;
			}
		}
		$entity = $this->patchEntity($entity, $saveData);
		if (!empty($options['dirtyFields'])) {
			$fieldsToDirty = array_keys($saveData);
			if (is_array($options['dirtyFields']) || is_string($options['dirtyFields'])) {
				$fieldsToDirty = array_intersect($fieldsToDirty, (array)$options['dirtyFields']);
			}
			foreach ($fieldsToDirty as $fieldName) {
				$entity->setDirty($fieldName, true);
			}
		}
		return $this->save($entity, $options);
	}

	/**
	 * Создаёт много сущностей из массива и сохраняет их
	 *
	 * @param array $saveData
	 * @param array $options
	 * @return array|bool|\Cake\ORM\ResultSet
	 */
	public function saveManyArr($saveData, $options = [])
	{
		if (!is_array($saveData)) {
			return false;
		}
		return $this->saveMany($this->newEntities($saveData, $options));
	}

	/**
	 * Если аргумент - сущность, то её и возвращает
	 * Если число, то вытаскивает по нему сущность
	 * Иначе - false
	 *
	 * @param Entity|int $entity
	 * @param array $options
	 * @return Entity|false
	 */
	public function getEntity($entity, $options = [])
	{
		if ($entity instanceof Entity) {
			return $entity;
		}
		if (empty($entity)) {
			return false;
		}
		try {
			return $this->get($entity, $options);
		} catch (RecordNotFoundException $e) {
			return false;
		}
	}

	/**
	 * Проверка на существование записей
	 *
	 * @param array $conditions
	 * @param array $contain
	 * @return bool
	 */
	public function exists($conditions, $contain = [])
	{
		return (bool)count(
			$this->find('all')
				->select(['existing' => 1])
				->contain($contain)
				->where($conditions)
				->limit(1)
				->enableHydration(false)
				->toArray()
		);
	}

	/**
	 * Ищем одну запись и редактируем её с блокировкой
	 *
	 * @param Query|array $queryData
	 * @param array $updateData
	 * @return Entity|null
	 */
	public function updateWithLock($queryData, $updateData)
	{
		if (is_array($queryData)) {
			$queryData = $this->find()->where($queryData);
		}
		return $this->getConnection()->transactional(function () use ($queryData, $updateData) {
			$result = $queryData->epilog('FOR UPDATE')
				->first();
			if (empty($result)) {
				return $result;
			} else {
				$this->patchEntity($result, $updateData);
				return $this->save($result);
			}
		});
	}

	/**
	 * @inheritdoc
	 * добавил опцию одноразового переопределения способа сохранения ассоциаций
	 * изменённым дочерним сущностям проставляется dirty
	 */
	public function save(EntityInterface $entity, $options = [])
	{
		$this->_setAssocDirty($entity);
		$originalStrategies = [];
		if (!empty($options['assocStrategies'])) {
			foreach ($options['assocStrategies'] as $assoc => $strategy) {
				$originalStrategies[$assoc] = $this->$assoc->getSaveStrategy();
				$this->$assoc->setSaveStrategy($strategy);
			}
			unset($options['assocStrategies']);
		}
		$result = parent::save($entity, $options);
		foreach ($originalStrategies as $assoc => $strategy) {
			$this->$assoc->setSaveStrategy($strategy);
		}
		return $result;
	}

	/**
	 * Очистить таблицу
	 *
	 * @return bool
	 */
	public function truncate()
	{
		return ((int)$this->getConnection()->execute('TRUNCATE ' . $this->getTable())->errorCode() === 0);
	}

	/**
	 * Очистить таблицу, если нельзя применить truncate
	 * (есть внешние ключи или должна быть возможность откатиться)
	 *
	 * @return int
	 */
	public function truncateSafe()
	{
		return $this->deleteAll(Query::CONDITION_ALL);
	}

	/**
	 * Обновить все строки таблицы
	 *
	 * @param array $fields
	 * @return int
	 */
	public function updateAllRecords($fields)
	{
		return $this->updateAll($fields, Query::CONDITION_ALL);
	}

	/**
	 * @inheritdoc
	 * Добавил возможность более коротких опций
	 */
	public function findList(\Cake\ORM\Query $query, array $options)
	{
		if ((count($options) === 1) && empty($options['valueField'])) {
			$newOptions = [];
			foreach ($options as $keyField => $valueField) {
				$selectFields = [$valueField];
				if (is_int($keyField)) {
					$keyField = $valueField;
				} else {
					$selectFields[] = $keyField;
				}
				foreach ($selectFields as $field) {
					$path = explode('.', $field);
					$fieldName = array_pop($path);
					if (empty($path)) {
						$alias = $this->getAlias();
					} else {
						$alias = array_pop($path);
					}
					$query->select("$alias.$fieldName");
				}
				$newOptions = [
					'keyField' => $keyField,
					'valueField' => $valueField,
				];
			}
			$options = $newOptions;
		}
		return parent::findList($query, $options);
	}

	/** @inheritdoc */
	public function query()
	{
		return new Query($this->getConnection(), $this);
	}

	/** @inheritdoc */
	public function belongsTo($associated, array $options = [])
	{
		return parent::belongsTo($associated, $this->_assocOptions($associated, $options));
	}

	/** @inheritdoc */
	public function hasOne($associated, array $options = [])
	{
		return parent::hasOne($associated, $this->_assocOptions($associated, $options));
	}

	/** @inheritdoc */
	public function hasMany($associated, array $options = [])
	{
		return parent::hasMany($associated, $this->_assocOptions($associated, $options));
	}

	/** @inheritdoc */
	public function belongsToMany($associated, array $options = [])
	{
		return parent::belongsToMany($associated, $this->_assocOptions($associated, $options));
	}

	/**
	 * Автозаполнение полей создания/правки
	 */
	private function _initTimeStampBehavior()
	{
		$timeStampFields = [];
		$columnList = $this->getSchema()->columns();

		if (in_array('created', $columnList, true)) {
			$timeStampFields['created'] = 'new';
		}
		if (in_array('updated', $columnList, true)) {
			$timeStampFields['updated'] = 'always';
		}
		if (in_array('modified', $columnList, true)) {
			$timeStampFields['modified'] = 'always';
		}

		if (!empty($timeStampFields)) {
			$this->addBehavior('Timestamp', [
				'events' => [
					'Model.beforeSave' => $timeStampFields,
				],
			]);
		}
	}

	/**
	 * Пройтись по ассоциациям и задать им dirty, если надо
	 *
	 * @param EntityInterface $entity
	 */
	private function _setAssocDirty(EntityInterface $entity)
	{
		$associations = $this->associations();
		foreach ($associations as $assoc) {
			$propertyName = $assoc->getProperty();
			if (empty($entity->{$propertyName})) {
				continue;
			}

			if (is_array($entity->{$propertyName})) {
				/** @var Entity $subEntity */
				foreach ($entity->{$propertyName} as $subEntity) {
					if ($subEntity->isDirty()) {
						$entity->setDirty($propertyName, true);
						break;
					}
				}
			} else {
				if ($entity->{$propertyName}->isDirty()) {
					$entity->setDirty($propertyName, true);
				}
			}
		}
	}

	/**
	 * Возвращает алиас таблицы, используемый тут повсюду
	 *
	 * @return string
	 */
	private static function _getAlias()
	{
		$classNameParts = explode('\\', static::class);
		return Strings::replacePostfix(array_pop($classNameParts), 'Table');
	}

	/**
	 * Обработать опции создания ассоциаций
	 *
	 * @param string $assocName
	 * @param array $options
	 * @return array
	 */
	private function _assocOptions($assocName, $options)
	{
		if (empty($options['propertyName'])) {
			$options['propertyName'] = $assocName;
		}
		return $options;
	}
}
