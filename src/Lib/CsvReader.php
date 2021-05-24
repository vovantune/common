<?php

namespace ArtSkills\Lib;

use ArtSkills\Error\InternalException;

/**
 * TODO: переделать дефолтовый разделитель, поменять в конструкторе последние 2 параметра местами
 */
class CsvReader
{
    const DEFAULT_ENCODING = 'UTF-8';
    const DEFAULT_DELIMITER = ',';

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
    private $_delimiter = self::DEFAULT_DELIMITER;

    /**
     * CsvReader constructor.
     *
     * @param string $csvFl
     * @param string $delimiter
     * @param string $fileEncoding
     * @throws InternalException
     */
    public function __construct($csvFl, $delimiter = self::DEFAULT_DELIMITER, $fileEncoding = self::DEFAULT_ENCODING)
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
        $row = fgetcsv($this->_handle, null, $this->_delimiter);
        if (empty($row) || (count($row) == 1) && trim($row[0]) === '') {
            return false;
        } else {
            return $row;
        }
    }

    /**
     * Читает csv файл и возвращает массив
     *
     * @return string[]
     * @throws InternalException
     */
    public function getAll()
    {
        if (empty($this->_handle)) {
            throw new InternalException('File is not open');
        }

        $result = [];
        fseek($this->_handle, 0);
        while (($data = fgetcsv($this->_handle, null, $this->_delimiter)) !== false) {
            $result[] = $data;
        }

        return $result;
    }

    /**
     * Формируем ассоциативный массив из CSV файла, первая строка - имена элементов массива
     *
     * @return array|bool
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
    private function _openFile($csvFl, $fileEncoding = self::DEFAULT_ENCODING)
    {
        ini_set('auto_detect_line_endings', true);
        $handle = fopen($csvFl, 'r');
        stream_filter_append($handle, 'convert.iconv.' . $fileEncoding . '/UTF-8');
        return $handle;
    }

    /**
     * Закрываем открытый файл
     */
    private function _closeFile()
    {
        if (!empty($this->_handle)) {
            fclose($this->_handle);
            ini_set('auto_detect_line_endings', false);
            $this->_handle = null;
        }
    }
}
