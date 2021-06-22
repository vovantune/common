<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\Excel\ExcelWriterTest;

use ArtSkills\Error\InternalException;
use ArtSkills\Error\UserException;
use ArtSkills\Excel\ExcelReader;
use ArtSkills\Excel\ExcelWriter;
use ArtSkills\Excel\FieldMapElement;
use ArtSkills\Excel\IncorrectCheckException;
use ArtSkills\TestSuite\AppTestCase;

class ExcelWriterTest extends AppTestCase
{
    /**
     * Запись в файл
     *
     * @throws IncorrectCheckException
     * @throws InternalException
     * @throws UserException
     */
    public function testWrite()
    {
        $testFile = TMP . DS . 'ExcelWriterTest_testWrite.xlsx';
        $writer = new ExcelWriter($testFile);

        $pageName = 'testPage';
        $typeMap = [
            FieldMapElement::create('barcode', 1, FieldMapElement::TYPE_STRING),
            FieldMapElement::create('remains', 2, FieldMapElement::TYPE_INT),
            FieldMapElement::create('cost', 3, FieldMapElement::TYPE_MONEY),
        ];
        $writer->setSheetFieldMap($pageName, $typeMap);

        $writer->writeSheetRow($pageName, [
            'barcode' => '12345',
            'remains' => 4,
            'cost' => 66.66,
        ]);
        $writer->writeSheetRow($pageName, [
            'barcode' => '9876',
            'remains' => 8,
            'cost' => 333,
        ]);
        $writer->close();

        $result = ExcelReader::get($testFile, $typeMap, 1);
        self::assertEquals([
            0 => [
                'barcode' => '12345',
                'remains' => 4,
                'cost' => 66.66,
            ],
            1 => [
                'barcode' => '9876',
                'remains' => 8,
                'cost' => 333,
            ],
        ], $result);
        unlink($testFile);
    }

    /**
     * Запись с заголовком
     *
     * @throws IncorrectCheckException
     * @throws InternalException
     * @throws UserException
     */
    public function testWriteWithHeader()
    {
        $testFile = TMP . DS . 'ExcelWriterTest_testWrite.xlsx';
        $writer = new ExcelWriter($testFile);

        $pageName = 'testPage';
        $typeMap = [
            FieldMapElement::create('barcode', 1, FieldMapElement::TYPE_STRING),
            FieldMapElement::create('remains', 2, FieldMapElement::TYPE_INT),
            FieldMapElement::create('cost', 3, FieldMapElement::TYPE_MONEY),
        ];
        $writer->setSheetFieldMap($pageName, $typeMap, ['barcode' => 20, 'remains' => 10, 'cost' => 20])
            ->writeSheetHeader($pageName, [
                'barcode' => 'ШК',
                'remains' => 'Остаток',
                'cost' => 'Стоимость',
            ])->writeSheetRow($pageName, [
                'barcode' => '12345',
                'remains' => 4,
                'cost' => 66.66,
            ]);
        $writer->close();

        $result = ExcelReader::get($testFile, [
            FieldMapElement::create('barcode', 1, FieldMapElement::TYPE_STRING),
            FieldMapElement::create('remains', 2, FieldMapElement::TYPE_STRING),
            FieldMapElement::create('cost', 3, FieldMapElement::TYPE_STRING),
        ], 1);
        self::assertEquals([
            0 => [
                'barcode' => 'ШК',
                'remains' => 'Остаток',
                'cost' => 'Стоимость',
            ],
            1 => [
                'barcode' => '12345',
                'remains' => '4',
                'cost' => '66.66',
            ],
        ], $result);
        unlink($testFile);
    }
}
