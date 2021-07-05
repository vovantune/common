<?php
declare(strict_types=1);

namespace ArtSkills\ValueObject;

use ArtSkills\Error\InternalException;
use ArtSkills\Lib\Arrays;
use ArtSkills\Lib\Env;
use ArtSkills\Lib\Strings;
use ArtSkills\ORM\Entity;
use Cake\Error\Debugger;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Log\Log;
use ReflectionClass;

/**
 * Основной класс [объекта-значения](https://github.com/ArtSkills/common/src/ValueObject/README.md).
 *
 * @phpstan-ignore-next-line
 */
abstract class ValueObject implements \JsonSerializable, \ArrayAccess
{
    /**
     * Методы, которые не экспортируются через json_encode
     *
     * @var string[]
     */
    const EXCLUDE_EXPORT_PROPS = [];

    /**
     * Поля с типом Time
     *
     * @var string[]
     */
    const TIME_FIELDS = [];

    /**
     * Поля с типом Date
     *
     * @var string[]
     */
    const DATE_FIELDS = [];

    /**
     * Список экспортируемых свойств
     *
     * @var string[]
     */
    private array $_exportFieldNames = [];

    /**
     * Список всех полей
     *
     * @var array<string, string>
     */
    protected array $_allFieldNames = [];

    /**
     * constructor.
     *
     * @param array|Entity $fillValues Список заполняемых свойств
     * @throws InternalException
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public function __construct($fillValues = [])
    {
        $this->_fillExportedFields();

        foreach (static::TIME_FIELDS as $fieldName) {
            if (!empty($fillValues[$fieldName]) && (is_string($fillValues[$fieldName]) || is_int($fillValues[$fieldName]))) {
                $fillValues[$fieldName] = Time::parse($fillValues[$fieldName]);
            }
        }

        foreach (static::DATE_FIELDS as $fieldName) {
            if (!empty($fillValues[$fieldName]) && (is_string($fillValues[$fieldName]) || is_int($fillValues[$fieldName]))) {
                $fillValues[$fieldName] = Date::parse($fillValues[$fieldName]);
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
     * @SuppressWarnings(PHPMD.MethodArgs)
     * @phpstan-ignore-next-line
     */
    public static function create(array $fillValues = [])
    {
        return new static($fillValues); // @phpstan-ignore-line
    }

    /**
     * Возможность использовать цепочку вызовов ->setField1($value1)->setField2($value2)
     *
     * @param string $name
     * @param array<int, mixed> $arguments
     * @return $this
     * @throws InternalException
     */
    public function __call(string $name, array $arguments = [])
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
            if (in_array($propertyName, static::TIME_FIELDS) && (is_string($setValue) || is_int($setValue))) {
                $setValue = Time::parse($setValue);
            } elseif (in_array($propertyName, static::DATE_FIELDS) && (is_string($setValue) || is_int($setValue))) {
                $setValue = Date::parse($setValue);
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
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public function toArray(): array
    {
        return json_decode(json_encode($this), true);
    }

    /**
     * Преобразуем в json строку
     *
     * @return string
     */
    public function toJson(): string
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
     * @SuppressWarnings(PHPMD.MethodArgs)
     * @phpstan-ignore-next-line
     */
    public function jsonSerialize(): array
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
    private function _fillExportedFields(): void
    {
        $refClass = new ReflectionClass(static::class);
        $properties = $refClass->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            $propertyName = $property->getName();
            if (!in_array($propertyName, static::EXCLUDE_EXPORT_PROPS)) {
                $this->_exportFieldNames[] = $propertyName;
            }
            $this->_allFieldNames[$propertyName] = $propertyName;
        }
    }

    /**
     * @param string|int $offset
     */
    public function offsetExists($offset): bool
    {
        $this->_triggerDeprecatedError($offset);
        return property_exists($this, $offset);
    }

    /**
     * @param string|int $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $this->_triggerDeprecatedError($offset);
        return $this->{$offset};
    }

    /**
     * @param string|int $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->_triggerDeprecatedError($offset);
        $this->{$offset} = $value;
    }

    /**
     * @inheritdoc
     * @param string|int $offset
     */
    public function offsetUnset($offset)
    {
        $this->_triggerDeprecatedError($offset);
        $this->offsetSet($offset, null);
    }

    /**
     * Выводим сообщение о недопустимости обращения как к элементу массива
     *
     * @param string|int $offset
     */
    private function _triggerDeprecatedError($offset): void
    {
        $trace = Debugger::trace(['start' => 2, 'depth' => 3, 'format' => 'array']);
        $file = str_replace([CAKE_CORE_INCLUDE_PATH, ROOT], '', $trace[0]['file']);
        $line = $trace[0]['line'];

        Log::error("Deprecated array access to property " . static::class . "::" . $offset . " in $file($line)");
    }
}
