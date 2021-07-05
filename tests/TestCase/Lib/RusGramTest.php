<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\Lib;

use ArtSkills\Lib\RusGram;
use ArtSkills\TestSuite\AppTestCase;

class RusGramTest extends AppTestCase
{
    /**
     * Склонение
     */
    public function testDeclension(): void
    {
        $words = ['рубль', 'рубля', 'рублей'];

        self::assertEquals('1 рубль', RusGram::declension(1, $words), 'Некорректное склонение в именительный падеж');
        self::assertEquals('2 рубля', RusGram::declension(2, $words), 'Некорректное склонение в дательный падеж');
        self::assertEquals('10 рублей', RusGram::declension(10, $words), 'Некорректное склонение в родительный падеж');

        self::assertEquals('рубля', RusGram::declension(2, $words, true), 'Некорректный вывод без цифры');
    }

    /**
     * Дата по-русски
     */
    public function testGetRussianDate(): void
    {
        self::assertEquals('11 Окт октябрь Вс', RusGram::getRussianDate('d M FI D', '2015-10-11'), 'Некорректное формирование даты');
        self::assertEquals('11 октября Воскресенье', RusGram::getRussianDate('d FR l', '2015-10-11'), 'Некорректное формирование даты');
    }

    /** Число в строку */
    public function testNumberToString(): void
    {
        self::assertEquals('сто тысяч рублей 00 копеек', RusGram::numberToString(100000));
        self::assertEquals('семь тысяч пятьсот сорок шесть рублей 08 копеек', RusGram::numberToString(7546.08));
    }
}
