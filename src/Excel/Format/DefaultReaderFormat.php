<?php
declare(strict_types=1);

namespace ArtSkills\Excel\Format;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class DefaultReaderFormat extends AbstractReaderFormat
{
    /**
     * @var Spreadsheet
     */
    private Spreadsheet $_spreadsheet;

    /**
     * DefaultReaderFormat constructor.
     *
     * @param string $fileName
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function __construct(string $fileName)
    {
        $this->_spreadsheet = IOFactory::load($fileName);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getCell(string $pCoordinate, int $page = 1)
    {
        $sheet = $this->_spreadsheet->getSheet($page - 1);
        return $sheet->getCell($pCoordinate, false);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getRows(int $page = 1, int $dataRowIndex = 1, bool $skipEmptyRows = true): ?array
    {
        $spreadsheet = $this->_spreadsheet;

        $sheet = $spreadsheet->getSheet($page - 1);
        $sheet->garbageCollect();
        $sheetSizeArr = $sheet->getCellCollection()->getHighestRowAndColumn(); // такой костыль нужен для защиты от каких-то необъятных размеров
        $maxRow = $sheetSizeArr['row'];
        $data = $sheet->rangeToArray('A' . $dataRowIndex . ':' . $sheetSizeArr['column'] . $maxRow, null, false, false, false);

        $result = [];
        foreach ($data as $workElement) {
            $hasElementData = false;
            foreach ($workElement as $index => $xlsFieldValue) {
                if ($xlsFieldValue === "#NULL!") {
                    $workElement[$index] = $xlsFieldValue = null;
                }

                if (!$hasElementData && $xlsFieldValue !== null) {
                    $hasElementData = true;
                }

                if (!$skipEmptyRows) {
                    $hasElementData = true;
                }
            }
            if ($hasElementData) {
                $result[] = $workElement;
            }
        }
        return $result;
    }
}
