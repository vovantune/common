<?php
namespace ArtSkills\Lib;

class CsvReader
{
	/**
	 * Читает csv файл и возвращает массив
	 *
	 * @param string $csvFile
	 * @param string $delimiter
	 * @return array
	 */
	public function parseCsv($csvFile, $delimiter = ",") {
		$result = [];
		ini_set('auto_detect_line_endings', true);
		$handle = fopen($csvFile, 'r');
		if (!$handle) {
			return [];
		}
		while (($data = fgetcsv($handle, null, $delimiter)) !== false) {
			$result[] = $data;
		}
		ini_set('auto_detect_line_endings', false);
		return $result;
	}

	/**
	 * Формируем ассоциативный массив из CSV файла, первая строка - имена элементов массива
	 *
	 * @param string $csvFile
	 * @param string $delimiter
	 * @return array|bool
	 */
	public function createAssocArrayFromCsv($csvFile, $delimiter = ",") {
		$lines = $this->parseCsv($csvFile, $delimiter);
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

}