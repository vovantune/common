<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\Lib;

use ArtSkills\Lib\Translit;
use ArtSkills\TestSuite\AppTestCase;

class TranslitTest extends AppTestCase
{
    /**
     * Транслитерация строки
     */
    public function testTransliterate(): void
    {
        self::assertEquals('Vasya Pupkin', Translit::transliterate('Вася Пупкин'));
        self::assertEquals('Petya 123 Xmur', Translit::transliterate('Петя 123 Xmur'));
    }

    /**
     * Псевдоним строки на английском языке
     */
    public function testGenerateUrlAlias(): void
    {
        self::assertEquals('vasya_pupkyan_556', Translit::generateUrlAlias('Вася  Пупкян-_-#556/\\"'));
    }
}
