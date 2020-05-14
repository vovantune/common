<?php

namespace ArtSkills\ValueObject;

use ArtSkills\Error\InternalException;
use ArtSkills\Lib\Arrays;
use ArtSkills\Lib\Env;
use ArtSkills\Lib\Strings;
use ArtSkills\ORM\Entity;
use Cake\Error\Debugger;
use Cake\I18n\Time;
use Cake\Log\Log;

/**
 * Основной класс [объекта-значения](https://github.com/ArtSkills/common/src/ValueObject/README.md).
 * TODO: 1) изучить https://symfony.com/doc/current/components/serializer.html для вомзможного применения
 * TODO: 2) проверять значения свойств при их заполнении на соответствии типа из PHPDoc
 */
abstract class ValueObject implements \JsonSerializable, \ArrayAccess
{
	/** Методы, которые не экспортируются через json_encode */
	const EXCLUDE_EXPORT_PROPS = [];

	/**
	 * Поля с типом Time
	 *
	 * @var array
	 */
	const TIME_FIELDS = [];

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
	 * constructor.
	 *
	 * @param array|Entity $fillValues Список заполняемых свойств
	 * @throws InternalException
	 */
	public function __construct($fillValues = [])
	{
		$this->_fillExportedFields();

		foreach (static::TIME_FIELDS as $fieldName) {
			if (!empty($fillValues[$fieldName]) && (is_string($fillValues[$fieldName]) || is_int($fillValues[$fieldName]))) {
				$fillValues[$fieldName] = Time::parse($fillValues[$fieldName]);
			}
		}

		foreach ($fillValues as $key => $value) {
			if (!property_exists($this, $key)) {
				throw new InternalException('Property ' . $key . ' not exists!');
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
	public static function create(array $fillValues = [])
	{
		return new static($fillValues);
	}

	/**
	 * Возможность использовать цепочку вызовов ->setField1($value1)->setField2($value2)
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return $this
	 * @throws InternalException
	 */
	public function __call($name, array $arguments = [])
	{
		$prefix = 'set';
		if (Strings::startsWith($name, $prefix)) {
			$propertyName = lcfirst(Strings::replacePrefix($name, $prefix));
			if (empty($this->_allFieldNames[$propertyName])) {
				throw new InternalException("Undefined property $propertyName");
			}
			if (count($arguments) !== 1) {
				throw new InternalException("Invalid argument count when calling $name");
			}
			$setValue = $arguments[0];
			if (in_array($propertyName, static::TIME_FIELDS) && [is_string($setValue) || is_int($setValue)]) {
				$setValue = Time::parse($setValue);
			}
			$this->{$propertyName} = $setValue;
			return $this;
		}
		throw new InternalException("Undefined method $name");
	}

	/**
	 * Преобразуем объект в массив, используется только для юнит тестов
	 *
	 * @return array
	 */
	public function toArray()
	{
		return json_decode(json_encode($this), true);
	}

	/**
	 * Преобразуем в json строку
	 *
	 * @return string
	 */
	public function toJson()
	{
		$options = JSON_UNESCAPED_UNICODE;
		if (Env::isDevelopment()) {
			$options |= JSON_PRETTY_PRINT;
		}
		return Arrays::encode($this, $options);
	}

	/**
	 * json_encode
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		$result = [];
		foreach ($this->_exportFieldNames as $fieldName) {
			$result[$fieldName] = $this->{$fieldName};
		}
		return $result;
	}

	/**
	 * Заполняем список полей на экспорт
	 */
	private function _fillExportedFields()
	{
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
	public function offsetExists($offset)
	{
		$this->_triggerDeprecatedError($offset);
		return property_exists($this, $offset);
	}

	/** @inheritdoc */
	public function offsetGet($offset)
	{
		$this->_triggerDeprecatedError($offset);
		return $this->{$offset};
	}

	/** @inheritdoc */
	public function offsetSet($offset, $value)
	{
		$this->_triggerDeprecatedError($offset);
		$this->{$offset} = $value;
	}

	/** @inheritdoc */
	public function offsetUnset($offset)
	{
		$this->_triggerDeprecatedError($offset);
		return $this->offsetSet($offset, null);
	}

	/**
	 * Выводим сообщение о недопустимости обращения как к элементу массива
	 *
	 * @param string $offset
	 */
	private function _triggerDeprecatedError($offset)
	{
		$trace = Debugger::trace(['start' => 2, 'depth' => 3, 'format' => 'array']);
		$file = str_replace([CAKE_CORE_INCLUDE_PATH, ROOT], '', $trace[0]['file']);
		$line = $trace[0]['line'];

		Log::error("Deprecated array access to property " . static::class . "::" . $offset . " in $file($line)", E_USER_ERROR);
	}
}
