<?php
declare(strict_types=1);

namespace ArtSkills\TestSuite\Mock;

use ArtSkills\Traits\Library;
use PHPUnit\Framework\AssertionFailedError;
use ReflectionProperty;

/**
 * Обращения к private и protected свойствам
 */
class PropertyAccess
{
    use Library;

    /**
     * Массив значений, которые нужно восстановить
     *
     * @var array
     */
    private static $_toRestore = [];

    /**
     * Запись в статическое свойство
     *
     * @param string $className
     * @param string $propertyName
     * @param mixed $value
     */
    public static function setStatic(string $className, string $propertyName, $value): void
    {
        $property = new ReflectionProperty($className, $propertyName);
        $property->setAccessible(true);
        $property->setValue($value);
    }

    /**
     * Запись в статическое свойство с возможностью восстановления
     *
     * @param string $className
     * @param string $propertyName
     * @param mixed $value
     */
    public static function setStaticAndRestore(string $className, string $propertyName, $value): void
    {
        $storeKey = self::_makeStoreKey($className, $propertyName);
        if (!array_key_exists($storeKey, self::$_toRestore)) {
            self::$_toRestore[$storeKey] = self::getStatic($className, $propertyName);
        }

        $property = new ReflectionProperty($className, $propertyName);
        $property->setAccessible(true);
        $property->setValue($value);
    }

    /**
     * Ключ, по которому будет храниться значение свойства
     *
     * @param string $className
     * @param string $propertyName
     * @return string
     */
    private static function _makeStoreKey(string $className, string $propertyName): string
    {
        return $className . '::' . $propertyName;
    }

    /**
     * Восстановить название класса и свойства из ключа хранения
     *
     * @param string $storeKey
     * @return string[]
     */
    private static function _getClassAndPropertyFromKey(string $storeKey): array
    {
        return explode('::', $storeKey);
    }

    /**
     * Восстановить статическое свойство после изменения
     *
     * @param string $className
     * @param string $propertyName
     * @throws \Exception
     */
    public static function restoreStatic(string $className, string $propertyName): void
    {
        $storeKey = self::_makeStoreKey($className, $propertyName);
        if (!array_key_exists($storeKey, self::$_toRestore)) {
            throw new AssertionFailedError("$storeKey was not modified");
        }
        self::setStatic($className, $propertyName, self::$_toRestore[$storeKey]);
        unset(self::$_toRestore[$storeKey]);
    }

    /**
     * Восстановить все изменённые статические свойства
     */
    public static function restoreStaticAll(): void
    {
        foreach (self::$_toRestore as $key => $value) {
            [$className, $propertyName] = self::_getClassAndPropertyFromKey($key);
            self::setStatic($className, $propertyName, $value);
        }
        self::$_toRestore = [];
    }

    /**
     * Запись в обычное свойство
     *
     * @param object $object
     * @param string $propertyName
     * @param mixed $value
     */
    public static function set($object, string $propertyName, $value): void
    {
        $property = new ReflectionProperty($object, $propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }


    /**
     * Чтение статического свойства
     *
     * @param string $className
     * @param string $propertyName
     * @return mixed
     */
    public static function getStatic(string $className, string $propertyName)
    {
        $property = new ReflectionProperty($className, $propertyName);
        $property->setAccessible(true);
        return $property->getValue();
    }

    /**
     * Чтение  обычного свойства
     *
     * @param object $object
     * @param string $propertyName
     * @return mixed
     */
    public static function get($object, string $propertyName)
    {
        $property = new ReflectionProperty(get_class($object), $propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
