<?php
declare(strict_types=1);

namespace ArtSkills\Lib;

use ArtSkills\Error\InternalException;
use ArtSkills\Traits\Library;
use ArtSkills\ValueObject\ValueObject;

class Arrays
{
    use Library;

    /**
     * json_encode с JSON_UNESCAPED_UNICODE по умолчанию
     *
     * @param array|ValueObject $array
     * @param int $options
     * @param int $depth
     * @return string
     * @SuppressWarnings(PHPMD.MethodArgs)
     * @phpstan-ignore-next-line
     */
    public static function encode($array, int $options = JSON_UNESCAPED_UNICODE, int $depth = 512): string
    {
        return json_encode($array, $options, $depth);
    }

    /**
     * json_decode, возвращающий по-умолчанию массив
     *
     * @param ?string $jsonString
     * @param bool $assoc
     * @param int $depth
     * @param int $options
     * @return array|null|string
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public static function decode(?string $jsonString, bool $assoc = true, int $depth = 512, int $options = 0)
    {
        if (empty($jsonString)) {
            return null;
        }

        return json_decode($jsonString, $assoc, $depth, $options);
    }


    /**
     * Взять из массива значения только по определённым ключам
     *
     * @param array $array
     * @param string[] $keys
     * @return array
     * @SuppressWarnings(PHPMD.MethodArgs)
     * @phpstan-ignore-next-line
     */
    public static function filterKeys(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Проставить массиву ключи на основе их значений
     * Возможно, вместо этой функции вам нужен array_flip()
     *
     * @param string[]|int[] $values
     * @return array<string|int, string|int>
     */
    public static function keysFromValues(array $values): array
    {
        return array_combine($values, $values);
    }

    /**
     * Переименовать ключи
     *
     * @param array<string, mixed> $array исходный массив
     * @param array<string, string> $map старый ключ => новый ключ
     * @param bool $notExistsNull если не найдено, то не добавлять или добавить null
     * @return array<string, mixed>
     */
    public static function remap(array $array, array $map, bool $notExistsNull = true): array
    {
        $newArray = [];
        foreach ($map as $oldKey => $newKey) {
            if (array_key_exists($oldKey, $array)) {
                $value = $array[$oldKey];
            } elseif ($notExistsNull) {
                $value = null;
            } else {
                continue;
            }
            $newArray[$newKey] = $value;
        }
        return $newArray;
    }

    /**
     * Получить значение по ключу с проверками
     *
     * @param ?array<string|int, mixed> $array
     * @param string|int $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(?array $array, $key, $default = null)
    {
        if (is_array($array) && array_key_exists($key, $array)) {
            return $array[$key];
        } else {
            return $default;
        }
    }

    /**
     * Проверить, что значение по ключу равно ожидаемому.
     * С проверкой на существование
     *
     * @param array<string|int, mixed> $array
     * @param string|int $key
     * @param mixed $value
     * @param bool $strict
     * @return bool
     */
    public static function equals(array $array, $key, $value, bool $strict = true): bool
    {
        if ($strict) {
            return array_key_exists($key, $array) && ($array[$key] === $value);
        } else {
            return array_key_exists($key, $array) && ($array[$key] == $value);
        }
    }

    /**
     * Проверить, что значение по ключу равно одному из ожидаемых
     *
     * @param array<string|int, mixed> $array
     * @param string|int $key
     * @param array<int, mixed> $values
     * @return bool
     */
    public static function equalsAny(array $array, $key, array $values): bool
    {
        return array_key_exists($key, $array) && in_array($array[$key], $values);
    }


    /**
     * Инициализировать значение в массиве по ключу или пути из ключей
     * Для уменьшения количества однообразных ифчиков вида
     * if (empty($array[$key])) $array[$key] = [];
     * if (empty($array[$key][$key2])) $array[$key][$key2] = [];
     * if (empty($array[$key][$key2][$key3])) $array[$key][$key2][$key3] = 1;
     *
     * @param array $array
     * @param string|string[] $keyPath
     * @param mixed $defaultValue
     * @return void
     * @SuppressWarnings(PHPMD.MethodArgs)
     * @phpstan-ignore-next-line
     * @throws InternalException
     */
    public static function initPath(array &$array, $keyPath, $defaultValue)
    {
        $keyPath = (array)$keyPath;
        $lastKey = array_pop($keyPath);
        foreach ($keyPath as $key) {
            if (!array_key_exists($key, $array)) {
                $array[$key] = [];
            } elseif (!is_array($array[$key])) {
                throw new InternalException("По ключу $key находится не массив");
            }
            $array = &$array[$key];
        }
        if (!array_key_exists($lastKey, $array)) {
            $array[$lastKey] = $defaultValue;
        }
    }
}
