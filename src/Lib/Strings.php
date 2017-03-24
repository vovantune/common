<?php
namespace ArtSkills\Lib;

use ArtSkills\Traits\Library;

class Strings
{
	use Library;

	/**
	 * Проверка, что строка начинается с префикса
	 *
	 * @param string $string
	 * @param string $prefix
	 * @return bool
	 */
	public static function startsWith($string, $prefix) {
		return (stripos($string, $prefix) === 0);
	}

	/**
	 * Проверка, что строка заканчивается постфиксом
	 *
	 * @param string $string
	 * @param string $postfix
	 * @return bool
	 */
	public static function endsWith($string, $postfix) {
		return (strripos($string, $postfix) === strlen($string));
	}

	/**
	 * Заменить или отрезать префикс у строки
	 * str_replace заменяет все вхождения, так что он не подходит
	 *
	 * @param string $string
	 * @param string $prefix
	 * @param string $replacement
	 * @return string
	 */
	public static function replacePrefix($string, $prefix, $replacement = '') {
		return $replacement . (substr($string, strlen($prefix)));
	}

	/**
	 * Заменить или отрезать поствикс у строки
	 * str_replace заменяет все вхождения, так что он не подходит
	 *
	 * @param string $string
	 * @param string $postfix
	 * @param string $replacement
	 * @return string
	 */
	public static function replacePostfix($string, $postfix, $replacement = '') {
		return (substr($string, 0, -strlen($postfix))) . $replacement;
	}



	/**
	 * сделать array_pop() от результата explode()
	 * для array_pop() нужно создавать временную переменную, что раздражает
	 *
	 * @param string $delimiter
	 * @param string $string
	 * @return string
	 */
	public static function lastPart($delimiter, $string) {
		$tmp = explode($delimiter, $string);
		return array_pop($tmp);
	}



	/**
	 * Реализация mb_ucfirst, если её нет
	 *
	 * @param string $string
	 * @param string $enc
	 * @return string
	 */
	public static function mbUcFirst($string, $enc = 'utf-8') {
		$string = mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc) . mb_substr($string, 1, mb_strlen($string, $enc) - 1, $enc);
		return $string;
	}

	/**
	 * Реализация mb_lcfirst, если её нет
	 *
	 * @param string $string
	 * @param string $enc
	 * @return string
	 */
	public static function mbLcFirst($string, $enc = 'utf-8') {
		$string = mb_strtolower(mb_substr($string, 0, 1, $enc), $enc) . mb_substr($string, 1, mb_strlen($string, $enc) - 1, $enc);
		return $string;
	}

}