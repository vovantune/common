<?php
declare(strict_types=1);

namespace ArtSkills\Lib;

use ArtSkills\Error\InternalException;
use Exception;

/**
 * Запись в CSV файл. Работает в двух решимах:
 *  - Построчная запись (для больших объёмов данных)
 * ```php
 * $svFile = new CsvWriter('svFile.csv', ',', 'cp1251');
 * while ($data = $query->fetchRow()) {
 *     $svFile->writeRow($data);
 * }
 * $svFile->close(); // также возможно сделать unset($svFile) - сохраняет данные при вызове деструктора
 * ```
 * - Запись ассоциативного массива целиком
 * ```php
 * $result = CsvWriter::writeCsv('svFile.csv', $lines, ',', 'cp1251');
 * ```
 * @SuppressWarnings(PHPMD.MethodMix)
 */
class CsvWriter
{
    public const DEFAULT_ENCLOSURE = '"';

    /**
     * Указатель на файл
     *
     * @var ?resource
     */
    private $_handle;
    /**
     * Разделитель колонок
     *
     * @var string
     */
    private string $_delimiter = CsvReader::DEFAULT_DELIMITER;

    /**
     * CsvWriter constructor.
     *
     * @param string $filename
     * @param string $delimiter
     * @param string $encoding
     * @throws InternalException
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     */
    public function __construct(
        string $filename,
        string $delimiter = CsvReader::DEFAULT_DELIMITER,
        string $encoding = CsvReader::DEFAULT_ENCODING
    ) {
        // phpcs:ignore
        @$this->_handle = fopen($filename, 'w');

        if (!$this->_handle) {
            throw new InternalException('Ошибка создания файла ' . $filename);
        }
        stream_filter_append($this->_handle, 'convert.iconv.UTF-8/' . $encoding . '//TRANSLIT//IGNORE');
        $this->_delimiter = $delimiter;
    }

    /**
     * Запись строки
     *
     * @param array<string|int, string|int|bool|float|null> $row
     * @return int
     * @throws Exception
     */
    public function writeRow(array $row): int
    {
        if (!$this->_handle) {
            throw new Exception('Попытка записать в закрытый файл');
        }
        return fputcsv($this->_handle, $row, $this->_delimiter);
    }

    /**
     * Закрывает файл
     *
     * @return void
     */
    public function close()
    {
        fclose($this->_handle);
        $this->_handle = null;
    }

    /**
     * Деструктор и в Африке деструктор
     */
    public function __destruct()
    {
        if ($this->_handle) {
            $this->close();
        }
    }

    /**
     * Выгружает CSV файл из массива data
     *
     * @param string $filename
     * @param array<string|int, array<string|int, string|int|bool|float|null>> $data
     * @param string $delimiter
     * @param string $encoding
     * @param string $enclosure
     * @return bool
     */
    public static function writeCsv(
        string $filename,
        array $data,
        string $delimiter = CsvReader::DEFAULT_DELIMITER,
        string $encoding = CsvReader::DEFAULT_ENCODING,
        string $enclosure = self::DEFAULT_ENCLOSURE
    ): bool {
        $handle = fopen($filename, 'w');
        stream_filter_append($handle, 'convert.iconv.UTF-8/' . $encoding);
        if (!$handle) {
            return false;
        }
        foreach ($data as $line) {
            fputcsv($handle, $line, $delimiter, $enclosure);
        }

        fclose($handle);

        return true;
    }
}
