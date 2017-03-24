<?php
namespace ArtSkills\Lib;

use Cake\Error\Debugger;
use Cake\Log\Log;

class ValueObject implements \JsonSerializable, \ArrayAccess
{
	/** Методы, которые не экспортируются через json_encode */
	const EXCLUDE_EXPORT_PROPS = [];

	/**
	 * Список экспортируемых свойств
	 *
	 * @var string[]
	 */
	private $_exportFieldNames = [];

	/**
	 * Список всех полей
	 *
	 * @var array
	 */
	protected $_allFieldNames = [];

	/**
	 * ResponseEntity constructor.
	 *
	 * @param array $fillValues Список заполняемых свойств
	 * @throws \Exception
	 */
	public function __construct(array $fillValues = []) {
		$this->_fillExportedFields();

		foreach ($fillValues as $key => $value) {
			if (!property_exists($this, $key)) {
				throw new \Exception('Property ' . $key . ' not exists!');
			}

			$this->{$key} = $value;
		}
	}

	/**
	 * Создание через статический метод
	 *
	 * @param array $fillValues Список заполняемых свойств
	 * @return static
	 */
	public static function create(array $fillValues = []) {
		return new static($fillValues);
	}

	/**
	 * Возможность использовать цепочку вызовов ->setField1($value1)->setField2($value2)
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return $this
	 * @throws \Exception
	 */
	public function __call($name, array $arguments = []) {
		$prefix = 'set';
		if (Strings::startsWith($name, $prefix)) {
			$propertyName = lcfirst(Strings::replacePrefix($name, $prefix));
			if (empty($this->_allFieldNames[$propertyName])) {
				throw new \Exception("Undefined property $propertyName");
			}
			if (count($arguments) !== 1) {
				throw new \Exception("Invalid argument count when calling $name");
			}
			$this->{$propertyName} = $arguments[0];
			return $this;
		}
		throw new \Exception("Undefined method $name");
	}

	/**
	 * Преобразуем объект в массив, используется только для юнит тестов
	 *
	 * @return array
	 */
	public function toArray() {
		return json_decode(json_encode($this), true);
	}

	/**
	 * json_encode
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		$result = [];
		foreach ($this->_exportFieldNames as $fieldName) {
			$result[$fieldName] = $this->{$fieldName};
		}
		return $result;
	}

	/**
	 * Заполняем список полей на экспорт
	 */
	private function _fillExportedFields() {
		$refClass = new \ReflectionClass(static::class);
		$properties = $refClass->getProperties(\ReflectionProperty::IS_PUBLIC);
		foreach ($properties as $property) {
			$propertyName = $property->getName();
			if (!in_array($propertyName, static::EXCLUDE_EXPORT_PROPS)) {
				$this->_exportFieldNames[] = $propertyName;
			}
			$this->_allFieldNames[$propertyName] = $propertyName;
		}
	}

	/** @inheritdoc */
	public function offsetExists($offset) {
		$this->_triggerDeprecatedError($offset);
		return property_exists($this, $offset);
	}

	/** @inheritdoc */
	public function offsetGet($offset) {
		$this->_triggerDeprecatedError($offset);
		return $this->{$offset};
	}

	/** @inheritdoc */
	public function offsetSet($offset, $value) {
		$this->_triggerDeprecatedError($offset);
		$this->{$offset} = $value;
	}

	/** @inheritdoc */
	public function offsetUnset($offset) {
		$this->_triggerDeprecatedError($offset);
		return $this->offsetSet($offset, null);
	}

	/**
	 * Выводим сообщение о недопустимости обращения как к элементу массива
	 *
	 * @param string $offset
	 */
	private function _triggerDeprecatedError($offset) {
		$trace = Debugger::trace(['start' => 2, 'depth' => 3, 'format' => 'array']);
		$file = str_replace([CAKE_CORE_INCLUDE_PATH, ROOT], '', $trace[0]['file']);
		$line = $trace[0]['line'];

		Log::error("Deprecated array access to property " . static::class . "::" . $offset . " in $file($line)", E_USER_ERROR);
	}
}
