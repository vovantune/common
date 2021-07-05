<?php
declare(strict_types=1);

use ArtSkills\Error\InternalException;

/**
 * Функция для удобства создания вложенных ассоциаций. Соединяет аргументы через точку
 *
 * @param string ...$names
 * @return string
 */
function assoc(...$names): string
{
    return implode('.', $names);
}

/**
 * Функция для удобства обращения к полям при построении запросов
 *
 * @param string $tableAlias
 * @param string $fieldName
 * @param ?string $operation сравнения, (NOT) IN, LIKE, IS NULL, и всё такое
 * @return string
 */
function field(string $tableAlias, string $fieldName, ?string $operation = null): string
{
    return $tableAlias . '.' . $fieldName . (empty($operation) ? '' : ' ' . $operation);
}

/**
 * В массиве для условий where() всем полям проставить таблицу.
 * Проставляет таблицу в ключи массива.
 * Поэтому не будет работать для условий типа ['field IS NULL'].
 * Но будет работать для ['field IS' => null]
 * ```php
 * // можно делать
 * $query->where(fieldsWhere([
 *        'TableName1' => [
 *            'field1' => 'val1',
 *            'field2' => 'val2',
 *        ],
 *        'TableName2' => [
 *            'field1' => 'val3',
 *            'field2' => 'val4',
 *        ],
 *        '' => [
 *            'field3' => 'val',
 *        ]
 *    ]));
 * // вместо
 * $query->where([
 *        'TableName1.field1' => 'val1',
 *        'TableName1.field2' => 'val2',
 *        'TableName2.field1' => 'val3',
 *        'TableName2.field2' => 'val4',
 *        'field3' => 'val',
 *    ]);
 * ```
 *
 * @param array<string, array<string, mixed>> $conditionsByTable
 * @return array<string, mixed>
 * @throws InternalException при дублировании ключей
 */
function fieldsWhere(array $conditionsByTable): array
{
    $noTableKey = '';
    if (array_key_exists($noTableKey, $conditionsByTable)) {
        $newConditions = $conditionsByTable[$noTableKey];
        unset($conditionsByTable[$noTableKey]);
    } else {
        $newConditions = [];
    }
    foreach ($conditionsByTable as $tableAlias => $conditions) {
        foreach ($conditions as $field => $value) {
            $fieldFull = $tableAlias . '.' . $field;
            if (array_key_exists($fieldFull, $newConditions)) {
                throw new InternalException("Дублируется ключ $fieldFull");
            }
            $newConditions[$fieldFull] = $value;
        }
    }
    return $newConditions;
}

/**
 * В массиве для выборки select() всем полям проставить таблицу.
 * Проставляет таблицу в значения массива.
 * ```php
 * // можно делать
 * $query->select(fieldsSelect([
 *        'Table1' => [
 *            'field1',
 *            'field2',
 *        ],
 *        'Table2' => [
 *            'field1',
 *            'alias' => 'field2',
 *        ],
 *        '' => [
 *            'field4',
 *            'other_alias' => 'field5',
 *        ],
 *    ]));
 * // вместо
 * $query->select([
 *        'Table1.field1',
 *        'Table1.field2',
 *        'Table2.field1',
 *        'alias' => 'Table2.field2',
 *        'field4',
 *        'other_alias' => 'field5',
 *    ]);
 * ```
 *
 * @param array<string, array<string|int, mixed>> $fieldsByTable
 * @return string[]
 * @throws InternalException при дублировании ключей
 */
function fieldsSelect(array $fieldsByTable): array
{
    $noTableKey = '';
    if (array_key_exists($noTableKey, $fieldsByTable)) {
        $newFields = $fieldsByTable[$noTableKey];
        unset($fieldsByTable[$noTableKey]);
    } else {
        $newFields = [];
    }
    $counterKey = count($newFields);
    foreach ($fieldsByTable as $tableAlias => $fields) {
        foreach ((array)$fields as $key => $field) {
            if (!is_string($key)) {
                $key = $counterKey;
                $counterKey++;
            } elseif (array_key_exists($key, $newFields)) {
                throw new InternalException("Дублируется ключ $key");
            }
            $newFields[$key] = $tableAlias . '.' . $field;
        }
    }

    return $newFields;
}

if (!function_exists('mb_ucfirst') && function_exists('mb_substr')) {

    /**
     * Переводим в верхний регистр первую букву
     *
     * @param string $string
     * @param string $enc
     * @return string
     */
    function mb_ucfirst(string $string, string $enc = 'utf-8'): string
    {
        $string = mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc) . mb_strtolower(mb_substr($string, 1, mb_strlen($string, $enc) - 1, $enc), $enc);
        return $string;
    }
}
