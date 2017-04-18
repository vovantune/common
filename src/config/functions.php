<?php
/**
 * Функция для удобства создания вложенных ассоциаций. Соединяет аргументы через точку
 *
 * @param array ...$names
 * @return string
 */
function assoc(...$names) {
	return implode('.', $names);
}

/**
 * Функция для удобства обращения к полям при построении запросов
 *
 * @param string $tableAlias
 * @param string $fieldName
 * @param null $operation сравнения, (NOT) IN, LIKE, IS NULL, и всё такое
 * @return string
 */
function field($tableAlias, $fieldName, $operation = null) {
	return $tableAlias . '.' . $fieldName . (empty($operation) ? '' : ' ' . $operation);
}

if (!function_exists('mb_ucfirst') && function_exists('mb_substr')) {

	/**
	 * Переводим в верхний регистр первую букву
	 *
	 * @param string $string
	 * @param string $enc
	 * @return string
	 */
	function mb_ucfirst($string, $enc = 'utf-8') {
		$string = mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc) . mb_strtolower(mb_substr($string, 1, mb_strlen($string, $enc) - 1, $enc), $enc);
		return $string;
	}
}