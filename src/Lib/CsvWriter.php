<?php
namespace ArtSkills\Lib;

/**
 * Запись в CSV файл. Работает в двух решимах:
 *  - Построчная запись (для больших объёмов данных)
 * ```php
 * $svFile = new CsvWriter('svFile.csv', 'cp1251', ',');
 * while ($data = $query->fetchRow()) {
 *     $svFile->writeRow($data);
 * }
 * $svFile->close(); // также возможно сделать unset($svFile) - сохраняет данные при вызове деструктора
 * ```
 *
 * - Запись ассоциативного массива целиком
 * ```php
 * $result = CsvWriter::writeCsv('svFile.csv', $lines, 'cp1251', ',');
 * ```
 */
class CsvWriter
{
	/**
	 * Указатель на файл
	 *
	 * @var resource
	 */
	private $_handle = null;
	/**
	 * Разделитель колонок
	 *
	 * @var string
	 */
	private $_delimiter = ';';

	/**
	 * CsvWriter constructor.
	 *
	 * @param string $filename
	 * @param string $encoding
	 * @param string $delimiter
	 * @throws \Exception
	 */
	public function __construct($filename, $encoding = "UTF-8", $delimiter = ';') {
		@$this->_handle = fopen($filename, 'w');

		if (!$this->_handle) {
			throw new \Exception('Ошибка создания файла ' . $filename);
		}
		stream_filter_append($this->_handle, 'convert.iconv.UTF-8/' . $encoding . '//TRANSLIT//IGNORE');
		$this->_delimiter = $delimiter;
	}


	/**
	 * Запись строки
	 *
	 * @param array $row
	 * @return mixed
	 * @throws \Exception
	 */
	public function writeRow($row) {
		if (!$this->_handle) {
			throw new \Exception('Попытка записать в закрытый файл');
		}
		return fputcsv($this->_handle, $row, $this->_delimiter);
	}

	/**
	 * Закрывает файл
	 */
	public function close() {
		fclose($this->_handle);
		$this->_handle = null;
	}

	/**
	 * Деструктов и в Африке деструктор
	 */
	public function __destruct() {
		if ($this->_handle) {
			$this->close();
		}
	}

	/**
	 * Выгружает CSV файл из массива data
	 *
	 * @param string $filename
	 * @param array $data
	 * @param string $encoding
	 * @param string $delimiter
	 * @param string $enclosure
	 * @return bool
	 */
	public static function writeCsv(
		$filename, $data, $encoding = "UTF-8", $delimiter = ',', $enclosure = '"') {
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