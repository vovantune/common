<?php
declare(strict_types=1);

namespace ArtSkills\Lib\Excel;

use ArtSkills\Lib\Excel\Format\AbstractReaderFormat;
use ArtSkills\Error\InternalException;
use ArtSkills\Error\UserException;
use ArtSkills\Traits\Library;
use Exception;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

/**
 * Чтение Excel документов в формат ассоциативного массива
 */
class ExcelReader
{
    use Library;

    /**
     * Считываем значение ячейки
     *
     * @param string|AbstractReaderFormat $filePathOrReader
     * @param string $pCoordinate
     * @param int $page Нумерация с 1
     * @return Cell|null
     * @throws InternalException
     * @throws UserException
     * @throws Exception
     */
    public static function getCell($filePathOrReader, string $pCoordinate, int $page = 1): ?Cell
    {
        if ($page <= 0) {
            throw new InternalException('Некорректный параметр $page: ' . $page);
        }
        if ($filePathOrReader instanceof AbstractReaderFormat) {
            $reader = $filePathOrReader;
        } else {
            $reader = AbstractReaderFormat::getInstance($filePathOrReader);
        }
        return $reader->getCell($pCoordinate, $page);
    }

    /**
     * Считываем документ в ассоциативный массив
     *
     * @param string $filePath
     * @param FieldMapElement[] $fieldMap
     * @param int $dataRowIndex С какой колонки начинаются данные (нумерация с 1)
     * @param int $page С какой страницы считывать (нумерация с 1)
     * @param CheckCondition|null $checkDocumentField
     * @return array<int, array<string, int|string|float>>|null
     * @throws InternalException
     * @throws IncorrectCheckException
     * @throws UserException
     * @throws Exception
     */
    public static function get(string $filePath, array $fieldMap, int $dataRowIndex = 2, int $page = 1, ?CheckCondition $checkDocumentField = null): ?array
    {
        if ($dataRowIndex < 1) {
            throw new InternalException("Некорректный параметр dataColIndex: " . $dataRowIndex);
        }

        if ($page < 1) {
            throw new InternalException("Некорректный параметр page: " . $page);
        }
        $reader = AbstractReaderFormat::getInstance($filePath);
        if (!empty($checkDocumentField)) {
            $checkData = self::getCell($reader, $checkDocumentField->address, $page);
            if ((string)$checkData !== $checkDocumentField->data) {
                throw new IncorrectCheckException();
            }
        }
        $data = $reader->getRows($page, $dataRowIndex);
        $result = [];
        foreach ($data as $workElement) {
            $resultElement = [];
            $hasElementData = false;
            foreach ($fieldMap as $fieldMapElement) {
                if (array_key_exists($fieldMapElement->workIndex, $workElement)) {
                    $xlsFieldValue = $workElement[$fieldMapElement->workIndex];

                    if (!$hasElementData && $xlsFieldValue !== null) {
                        $hasElementData = true;
                    }

                    switch ($fieldMapElement->type) {
                        case FieldMapElement::TYPE_INT:
                            $resultElement[$fieldMapElement->name] = (int)$xlsFieldValue;
                            break;

                        case FieldMapElement::TYPE_FLOAT:
                        case FieldMapElement::TYPE_MONEY:
                            $resultElement[$fieldMapElement->name] = (float)$xlsFieldValue;
                            break;

                        case FieldMapElement::TYPE_STRING:
                        default:
                            $fieldValue = trim((string)$xlsFieldValue);
                            $resultElement[$fieldMapElement->name] = $fieldMapElement->stringToLowerCase ? mb_strtolower($fieldValue) : $fieldValue;
                            break;
                    }
                } else {
                    $resultElement[$fieldMapElement->name] = $fieldMapElement->defaultValue;
                }
            }
            if ($hasElementData) {
                $result[] = $resultElement;
            }
        }

        return empty($result) ? null : $result;
    }

    /**
     * Считываем указанные строки документа в ассоциативный массив
     *
     * @param string $filePath Путь к файлу
     * @param int $startRow Строка с которой мы считываем
     * @param int $rowCount Сколько строк вывести
     * @param int $page Страница документа
     * @param bool $skipEmptyRows Пропускать пустые строки
     * @return array<int, array<string, string>>|null
     * @throws InternalException
     * @throws Exception
     */
    public static function getRows(string $filePath, int $startRow = 1, int $rowCount = 15, int $page = 1, bool $skipEmptyRows = true): ?array
    {
        if ($startRow < 1 || $rowCount < 1 || $page < 1) {
            throw new InternalException("Передаваемые аргументы должны быть больше или равными единице");
        }
        $reader = AbstractReaderFormat::getInstance($filePath);
        $data = $reader->getRows($page, $startRow, $skipEmptyRows);
        $result = [];
        $iteration = 0;
        foreach ($data as $row) {
            if ($iteration === $rowCount) {
                break;
            }
            $result[] = $row;
            $iteration++;
        }

        return empty($result) ? null : $result;
    }
}
