<?php
namespace ArtSkills\ORM;

class Entity extends \Cake\ORM\Entity
{

	/**
	 * Алиасы полей
	 *
	 * @var array
	 */
	protected $_aliases = [];

	/** @inheritdoc  */
	public function __construct(array $properties = [], array $options = []) {
		parent::__construct($properties, $options);
		$this->_virtual = array_merge($this->_virtual, array_keys($this->_aliases));
	}

	/**
	 * Проверить, алиас ли это, и вернуть настоящее св-во
	 *
	 * @param string $alias
	 * @return string
	 */
	private function _actualProperty($alias) {
		return empty($this->_aliases[$alias]) ? $alias : $this->_aliases[$alias];
	}

	/** @inheritdoc */
	public function set($property, $value = null, array $options = []) {
		if (is_array($property)) {
			$actualProperty = [];
			foreach ($property as $name => $value) {
				$actualName = $this->_actualProperty($name);
				$actualProperty[$actualName] = $value;
			}
		} elseif (is_string($property)) {
			$actualProperty = $this->_actualProperty($property);
		} else {
			$actualProperty = $property;
		}
		return parent::set($actualProperty, $value, $options);
	}

	/** @inheritdoc */
	public function &get($property) {
		return parent::get($this->_actualProperty($property));
	}

	/** @inheritdoc */
	public function getOriginal($property) {
		return parent::getOriginal($this->_actualProperty($property));
	}

	/**
	 * Ошибки без разделения по полям
	 * @return string[]
	 */
	public function getAllErrors() {
		$errorsByField = $this->errors();
		$errors = [];
		foreach ($errorsByField as $fieldErrors) {
			$errors = array_merge($errors, $fieldErrors);
		}
		return $errors;
	}

	/**
	 * Проверка, что значение поля изменилось
	 * потому что dirty() и extractOriginalChanged() могут срабатывать даже когда не изменилось, а при любом присвоении
	 *
	 * @param string $fieldName
	 * @return bool
	 */
	public function changed($fieldName) {
		return $this->get($fieldName) != $this->getOriginal($fieldName);
	}

	/**
	 * Удалить дочернюю сущность и проставить dirty
	 *
	 * @param string $childEntity
	 * @param null|int $index
	 * @throws \Exception
	 */
	public function deleteChild($childEntity, $index = null) {
		if (!array_key_exists($childEntity, $this->_properties)) {
			throw new \Exception("Unknown property $childEntity");
		} elseif (is_array($this->{$childEntity})) {
			if (is_null($index)) {
				$this->set($childEntity, []);
			} else {
				unset($this->{$childEntity}[$index]);
			}
		} else {
			$this->set($childEntity, null);
		}
		$this->dirty($childEntity, true);
	}


}