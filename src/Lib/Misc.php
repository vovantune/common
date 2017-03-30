<?php
namespace ArtSkills\Lib;

class Misc
{

	/**
	 * Нулевое время
	 */
	const ZERO_TIME = '0000-00-00 00:00:00';

	/**
	 * Разбить полное название класса на неймспейс и класс
	 *
	 * @param string $class
	 * @param bool $onlyClass
	 * @return string|string[]
	 */
	public static function namespaceSplit($class, $onlyClass = false) {
		$pos = strrpos($class, '\\');
		if ($pos === false) {
			$res = ['', $class];
		} else {
			$res = [substr($class, 0, $pos), substr($class, $pos + 1)];
		}
		if ($onlyClass) {
			return $res[1];
		} else {
			return $res;
		}
	}

	/**
	 * Соединить путь через DS
	 *
	 * @param string[] ...$parts
	 * @return string
	 */
	public static function implodeDs(...$parts) {
		return trim(implode(DS, $parts));
	}



}