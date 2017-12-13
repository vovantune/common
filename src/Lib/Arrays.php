<?php
namespace ArtSkills\Lib;

use ArtSkills\Error\InternalException;
use ArtSkills\Traits\Library;

class Arrays
{
	use Library;

	/**
	 * json_encode с JSON_UNESCAPED_UNICODE по умолчанию
	 *
	 * @param array|\ArtSkills\ValueObject\ValueObject $array
	 * @param int $options
	 * @param int $depth
	 * @return string
	 */
	public static function encode($array, $options = JSON_UNESCAPED_UNICODE, $depth = 512) {
		return json_encode($array, $options, $depth);
	}

	/**
	 * json_decode, возвращающий по-умолчанию массив
	 *
	 * @param string $jsonString
	 * @param bool $assoc
	 * @param int $depth
	 * @param int $options
	 * @return mixed
	 */
	public static function decode($jsonString, $assoc = true, $depth = 512, $options = 0) {
		return json_decode($jsonString, $assoc, $depth, $options);
	}



	/**
	 * Взять из массива значения только по определённым ключам
	 *
	 * @param array $array
	 * @param string[] $keys
	 * @return array
	 */
	public static function filterKeys(array $array, array $keys) {
		return array_intersect_key($array, array_flip($keys));
	}

	/**
	 * Проставить массиву ключи на основе их значений
	 * Возможно, вместо этой функции вам нужен array_flip()
	 *
	 * @param string[]|int[] $values
	 * @return array
	 */
	public static function keysFromValues(array $values) {
		return array_combine($values, $values);
	}

	/**
	 * Переименовать ключи
	 *
	 * @param array $array исхлдный массив
	 * @param array $map старый ключ => новый ключ
	 * @param bool $notExistsNull если не найдено, то не добавлять или добавить null
	 * @return array
	 */
	public static function remap(array $array, array $map, $notExistsNull = true) {
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
	 * @param array $array
	 * @param string|int $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get($array, $key, $default = null) {
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
	 * @param array $array
	 * @param string|int $key
	 * @param mixed $value
	 * @param bool $strict
	 * @return bool
	 */
	public static function equals(array $array, $key, $value, $strict = true) {
		if ($strict) {
			return array_key_exists($key, $array) && ($array[$key] === $value);
		} else {
			return array_key_exists($key, $array) && ($array[$key] == $value);
		}
	}

	/**
	 * Проверить, что значение по ключу равно одному из ожидаемых
	 *
	 * @param array $array
	 * @param string|int $key
	 * @param array $values
	 * @return bool
	 */
	public static function equalsAny(array $array, $key, array $values) {
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
	 * @throws InternalException
	 */
	public static function initPath(array &$array, $keyPath, $defaultValue) {
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



	/**
	 * strtolower для массива строк
	 *
	 * @param string[] $strings
	 * @return string[]
	 */
	public static function strToLower(array $strings) {
		return array_map('strtolower', $strings);
	}

	/**
	 * trim для массива строк
	 *
	 * @param string[] $strings
	 * @return string[]
	 */
	public static function trim(array $strings) {
		return array_map('trim', $strings);
	}

}