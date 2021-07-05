<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\Excel\DefaultReaderFormatTest;

use ArtSkills\Excel\Format\DefaultReaderFormat;
use ArtSkills\TestSuite\AppTestCase;
use PhpOffice\PhpSpreadsheet\Reader\Exception;

class DefaultReaderFormatTest extends AppTestCase
{
    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    public function testSkipEmptyRows(): void
    {
        $testFile = __DIR__ . DS . 'test_xls.xls';

        // Выводим пустые строки
        $defaultReaderFormat = new DefaultReaderFormat($testFile);
        $result = $defaultReaderFormat->getRows(1, 34, false);
        self::assertEquals([
            0 => [
                0 => null,
                1 => null,
                2 => null,
                3 => null,
                4 => null,
                5 => null,
                6 => null,
                7 => null,
                8 => null,
                9 => null,
                10 => null,
                11 => null,
                12 => null,
                13 => null,
                14 => null,
                15 => null,
                16 => null,
                17 => null,
                18 => null,
                19 => null,
                20 => null,
            ],
        ], $result);

        // Не выводим пустые строки
        $defaultReaderFormat = new DefaultReaderFormat($testFile);
        $result = $defaultReaderFormat->getRows(1, 34);
        self::assertEquals([], $result);
    }
}
