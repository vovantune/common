<?php

namespace ArtSkills\Test\TestCase\Lib\CsvWriterTest;

use ArtSkills\Lib\CsvReader;
use ArtSkills\Lib\CsvWriter;
use ArtSkills\TestSuite\AppTestCase;

class CsvWriterTest extends AppTestCase
{

    /**
     * Тест обычной записи файла
     */
    public function testNormal()
    {
        $testFile = TMP . 'csvWriterTest.csv';

        $csvWriter = new CsvWriter($testFile);
        $csvWriter->writeRow(['Привет', 'Мир']);
        $csvWriter->writeRow(['Вася', 'Пупкин']);
        $csvWriter->close();

        self::assertFileExists($testFile, 'Файл не был создан');
        self::assertFileEquals(__DIR__ . '/csvWriterOriginalFile.csv', $testFile, 'Файлы не идентичны!');
        unlink($testFile);
    }

    /**
     * Пытаемся записать в файл который не должен быть доступен
     */
    public function testWriteToNonExistsDirectory()
    {
        $this->expectExceptionMessage("Ошибка создания файла");
        $this->expectException(\Exception::class);
        $testFile = TMP . 'nonExistsDirectory/csvWriterTest.csv';

        $csvWriter = new CsvWriter($testFile);
        $csvWriter->writeRow(['Привет', 'Мир']);
        $csvWriter->writeRow(['Вася', 'Пупкин']);
        $csvWriter->close();
    }

    /**
     * Пытаемся записать в файл который закрыт
     */
    public function testWriteToClosedFile()
    {
        $this->expectExceptionMessage("Попытка записать в закрытый файл");
        $this->expectException(\Exception::class);
        $testFile = TMP . 'csvWriterTest.csv';

        $csvWriter = new CsvWriter($testFile);
        $csvWriter->writeRow(['Привет', 'Мир']);
        $csvWriter->writeRow(['Вася', 'Пупкин']);
        $csvWriter->close();
        unlink($testFile);
        $csvWriter->writeRow(['Вася', 'Пупкин']);
    }

    /**
     * Проверяет запись файла в другой кодировке
     *
     * @throws \Exception
     */
    public function testOtherEncoding()
    {
        $testFile = TMP . 'csvWriterTest.csv';

        $csvWriter = new CsvWriter($testFile, 'windows-1251');
        $csvWriter->writeRow(['Привет', 'Мир']);
        $csvWriter->writeRow(['Вася', 'Пупкин']);
        $csvWriter->close();

        self::assertFileExists($testFile, 'Файл не был создан');
        self::assertEquals(iconv('UTF-8', 'windows-1251', file_get_contents(__DIR__ . '/csvWriterOriginalFile.csv')), file_get_contents($testFile), 'Файлы не идентичны (кодировка)!');
        unlink($testFile);
    }

    /**
     * Проверка возможности корректной записи ковычек и всяких левых символов
     *
     * @throws \Exception
     */
    public function testWriteLeftSymbols()
    {
        $testFile = TMP . 'csvWriterTest.csv';

        $csvWriter = new CsvWriter($testFile);
        $csvWriter->writeRow(['При\\"вет"', "М'и'р"]);
        $csvWriter->writeRow(['"\'"\'Ва;ся', 'Пупк""ин']);
        $csvWriter->close();

        self::assertFileExists($testFile, 'Файл не был создан');
        self::assertFileEquals(__DIR__ . '/csvWriterOriginalFileWithSymbols.csv', $testFile, 'Файлы не идентичны!');
        unlink($testFile);
    }

    /**
     * Тест перенесеной функции записывающей весь массив
     */
    public function testWriteData()
    {
        $fileName = 'CsvTestTemp.csv';
        $data = [
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
        ];

        //Для проверки на корректность записанных данных
        self::assertEquals(true, CsvWriter::writeCsv($fileName, $data), 'Ошибка записи файла');
        self::assertEquals($data, (new CsvReader($fileName, ','))->getAll(), 'Некорректный массив из CSV файла');
        unlink($fileName);
    }
}
