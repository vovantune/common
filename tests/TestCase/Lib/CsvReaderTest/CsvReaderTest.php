<?php

namespace ArtSkills\Test\TestCase\Lib\CsvReaderTest;

use ArtSkills\Lib\CsvReader;
use ArtSkills\TestSuite\AppTestCase;

class CsvReaderTest extends AppTestCase
{

    /**
     * Проверка чтения сложных файлов
     */
    public function testReading()
    {
        $csv = new CsvReader(__DIR__ . '/CsvFixture.csv');
        self::assertEquals([
            0 => [
                0 => 'row1',
                1 => 'row2',
                2 => 'row3',
            ],
            1 => [
                0 => 'Вася',
                1 => '123.45',
                2 => '2015-04-16',
            ],
            2 => [
                0 => "П\"е'тя",
                1 => '1',
                2 => '1987-01-16',
            ],
        ], $csv->getAll(), 'Некорректный массив из CSV файла'); // обычная выгрузка

        self::assertEquals([
            0 => [
                'row1' => 'Вася',
                'row2' => '123.45',
                'row3' => '2015-04-16',
            ],
            1 => [
                'row1' => "П\"е'тя",
                'row2' => '1',
                'row3' => '1987-01-16',
            ],
        ], $csv->getAllAssoc(), 'Ассоциативный массив из CSV файла некорректен'); // ассоциативный массив
    }

    /**
     * Открываем несуществующий файл
     */
    public function testNotOpen()
    {
        $this->expectExceptionMessage("File \"bad file\" does not exist");
        $this->expectException(\Exception::class);
        new CsvReader('bad file');
    }
}
