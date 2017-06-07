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

/**
 * В массиве для условий where() всем полям проставить таблицу.
 *
 * @param string $tableAlias
 * @param array $conditions
 * @return array
 */
function fieldsWhere($tableAlias, array $conditions) {
	$newConditions = [];
	foreach ($conditions as $field => $value) {
		$fieldFull = $tableAlias . '.' . $field;
		$newConditions[$fieldFull] = $value;
	}
	return $newConditions;
}

/**
 * В массиве для выборки select() всем полям проставить таблицу.
 *
 * @param string $tableAlias
 * @param string|string[] $fields
 * @return string[]
 */
function fieldsSelect($tableAlias, $fields) {
	$fields = (array)$fields;
	foreach ($fields as &$field) {
		$field = $tableAlias . '.' . $field;
	}
	return $fields;
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