<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\Lib\Excel\ExcelReaderTest;

use ArtSkills\Lib\Excel\CheckCondition;
use ArtSkills\Lib\Excel\ExcelReader;
use ArtSkills\Lib\Excel\FieldMapElement;
use ArtSkills\Lib\Excel\IncorrectCheckException;
use ArtSkills\Error\InternalException;
use ArtSkills\TestSuite\AppTestCase;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Reader\Exception;

class ExcelReaderTest extends AppTestCase
{
    /**
     * Косячный файл
     */
    public function testNEFile()
    {
        $this->expectException(InvalidArgumentException::class);
        ExcelReader::get(__DIR__, [FieldMapElement::create('test', 1)]);
    }

    /**
     * Файл некорректного формата
     */
    public function testIncorrectFile()
    {
        $this->expectExceptionMessage("Unable to identify a reader for this file");
        $this->expectException(Exception::class);
        ExcelReader::get(__FILE__, [FieldMapElement::create('test', 1)]);
    }

    /**
     * Проверка файла XLSX
     */
    public function testGetCheckField()
    {
        $this->expectException(IncorrectCheckException::class);
        $testFile = __DIR__ . DS . 'test_xlsx.xlsx';
        ExcelReader::get($testFile, [FieldMapElement::create('test', 1)], 2, 1, CheckCondition::instance('D1', 'fdsfdsf'));
    }

    /** Читаем XLS с проверкой */
    public function testGetXls()
    {
        $testFile = __DIR__ . DS . 'test_xls.xls';
        $result = ExcelReader::get($testFile, [
            FieldMapElement::create('article', 2, FieldMapElement::TYPE_STRING, null, true),
            FieldMapElement::create('size', 4),
            FieldMapElement::create('count', 7, FieldMapElement::TYPE_INT),
        ], 2, 1, CheckCondition::instance('D1', 'Размер'));

        self::assertEquals([
            [
                'article' => 'fw20-dr01/',
                'size' => '44',
                'count' => 774,
            ],
            [
                'article' => 'fw20-sk03/',
                'size' => '48',
                'count' => 582,
            ],
            [
                'article' => 'fw20-dr01/',
                'size' => '48',
                'count' => 520,
            ],
        ], $result);
    }

    /**
     * Читаем в формате XLSX
     */
    public function testGetXlsx()
    {
        $testFile = __DIR__ . DS . 'test_xlsx.xlsx';
        $result = ExcelReader::get($testFile, [
            FieldMapElement::create('article', 2, FieldMapElement::TYPE_STRING),
            FieldMapElement::create('size', 4),
            FieldMapElement::create('count', 7, FieldMapElement::TYPE_INT),
        ], 2, 2, CheckCondition::instance('F1', 'Пол'));

        self::assertEquals([
            [
                'article' => '654321/',
                'size' => 'S',
                'count' => 100,
            ],
            [
                'article' => '654321/',
                'size' => 'S',
                'count' => 100,
            ],
            [
                'article' => '123456/',
                'size' => '0',
                'count' => 250,
            ],
            [
                'article' => 'артикул1/',
                'size' => '0',
                'count' => 50,
            ],
            [
                'article' => 'артикул2/',
                'size' => '36',
                'count' => 80,
            ],
        ], $result);
    }

    /** Чтение в формате ODS */
    public function testGetOds()
    {
        $testFile = __DIR__ . DS . 'test_ods.ods';
        $result = ExcelReader::get($testFile, [
            FieldMapElement::create('subject', 5, FieldMapElement::TYPE_STRING),
            FieldMapElement::create('priceBy', 9, FieldMapElement::TYPE_INT, 0),
            FieldMapElement::create('barcode', 14),
        ]);

        self::assertEquals([
            [
                'subject' => 'Платья',
                'priceBy' => 0,
                'barcode' => '2000162601037',
            ],
            [
                'subject' => 'Юбки',
                'priceBy' => 0,
                'barcode' => '2000152204033',
            ],
            [
                'subject' => 'Платья',
                'priceBy' => 0,
                'barcode' => '2000162601051',
            ],
        ], $result);
    }

    /** Получение строк */
    public function testGetRows()
    {
        $testFile = __DIR__ . DS . 'test_xls.xls';

        // Проверка вывода всего документа
        $result = ExcelReader::getRows($testFile);
        $actual = [
            'brand' => $result[0][0],
            'article' => $result[0][1],
        ];
        self::assertCount(4, $result);
        self::assertEquals([
            'brand' => 'Бренд',
            'article' => 'Артикул ИМТ',
        ], $actual);

        // Проверка параметров с какой строки и сколько строк
        $result = ExcelReader::getRows($testFile, 2, 2);
        $actual = [
            'brand' => $result[0][0],
            'article' => $result[1][1],
        ];
        self::assertCount(2, $result);
        self::assertEquals([
            'brand' => '4TEEN',
            'article' => 'FW20-sk03/',
        ], $actual);

        // Проверка параметров на меньше единицы
        try {
            ExcelReader::getRows($testFile, 0, 2);
        } catch (InternalException $exception) {
            self::assertEquals("Передаваемые аргументы должны быть больше или равными единице", $exception->getMessage());
        }
    }
}
