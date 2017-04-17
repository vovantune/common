<?php
namespace ArtSkills\Lib;

use ArtSkills\Traits\Library;

class Arrays
{
	use Library;

	/**
	 * json_encode с JSON_UNESCAPED_UNICODE по умолчанию
	 *
	 * @param array $array
	 * @param int $options
	 * @param int $depth
	 * @return string
	 */
	public static function encode(array $array, $options = JSON_UNESCAPED_UNICODE, $depth = 512) {
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
		return array_intersect_key($array, array_fill_keys($keys, 1));
	}

	/**
	 * Проставить массиву ключи на основе их значений
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