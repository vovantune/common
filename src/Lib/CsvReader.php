<?php
declare(strict_types=1);

namespace ArtSkills\Lib;

use ArtSkills\Error\InternalException;

class CsvReader
{
	public const DEFAULT_ENCODING = 'UTF-8';
	public const DEFAULT_DELIMITER = ';';

	/**
	 * Указатель на открытый файл
	 *
	 * @var null|resource
	 */
	private $_handle = null;

	/**
	 * Текущий разделитель
     *
     * @var string
     */
    private string $_delimiter = self::DEFAULT_DELIMITER;

    /**
     * CsvReader constructor.
     *
     * @param string $csvFl
     * @param string $delimiter
     * @param string $fileEncoding
     * @throws InternalException
     */
    public function __construct(string $csvFl, string $delimiter = self::DEFAULT_DELIMITER, string $fileEncoding = self::DEFAULT_ENCODING)
    {
        if (!is_file($csvFl)) {
            throw new InternalException('File "' . $csvFl . '" does not exist');
        }
        $this->_handle = $this->_openFile($csvFl, $fileEncoding);
        $this->_delimiter = $delimiter;
    }

    /** закрываем хэндл */
    public function __destruct()
    {
        $this->_closeFile();
    }

    /**
     * Одна запись
     *
     * @return string[]|false
     */
    public function getRow()
    {
		$row = fgetcsv($this->_handle, 0, $this->_delimiter);
        if (empty($row) || (count($row) == 1) && trim($row[0]) === '') {
            return false;
        } else {
            return $row;
        }
    }

    /**
     * Читает csv файл и возвращает массив
     *
     * @return array<int, string[]>
     * @throws InternalException
     */
    public function getAll(): array
    {
        if (empty($this->_handle)) {
            throw new InternalException('File is not open');
        }

        $result = [];
		fseek($this->_handle, 0);
		$data = fgetcsv($this->_handle, 0, $this->_delimiter);
        while ($data !== false) {
			$result[] = $data;
			$data = fgetcsv($this->_handle, 0, $this->_delimiter);
        }

        return $result;
    }

    /**
     * Формируем ассоциативный массив из CSV файла, первая строка - имена элементов массива
     *
     * @return array<int, array<string, string>>|bool
     * @throws \Exception
     */
    public function getAllAssoc()
    {
        $lines = $this->getAll();
        if (count($lines) < 2) {
            return false;
        }

        $names = $lines[0];
        unset($lines[0]);

        $result = [];
        foreach ($lines as $ln) {
            $ins = [];
            foreach ($names as $k => $nm) {
                $ins[$nm] = $ln[$k];
            }

            $result[] = $ins;
        }

        return $result;
    }

    /**
     * Открываем файл на чтение
     *
     * @param string $csvFl
     * @param string $fileEncoding
     * @return resource
     */
    private function _openFile(string $csvFl, string $fileEncoding = self::DEFAULT_ENCODING)
    {
        ini_set('auto_detect_line_endings', '1');
        $handle = fopen($csvFl, 'r');
        stream_filter_append($handle, 'convert.iconv.' . $fileEncoding . '/UTF-8');
        return $handle;
    }

	/**
	 * Закрываем открытый файл
	 *
	 * @return void
	 */
    private function _closeFile()
    {
        if (!empty($this->_handle)) {
            fclose($this->_handle);
            ini_set('auto_detect_line_endings', '0');
            $this->_handle = null;
        }
    }
}
