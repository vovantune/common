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
