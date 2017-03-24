<?php
namespace ArtSkills\Test\TestCase\Lib;

use ArtSkills\Lib\RusGram;
use ArtSkills\TestSuite\AppTestCase;

class RusGramTest extends AppTestCase
{
	/**
	 * Склонение
	 */
	public function testDeclension() {
		$words = ['рубль', 'рубля', 'рублей'];

		$this->assertEquals('1 рубль', RusGram::declension(1, $words), 'Некорректное склонение в именительный падеж');
		$this->assertEquals('2 рубля', RusGram::declension(2, $words), 'Некорректное склонение в дательный падеж');
		$this->assertEquals('10 рублей', RusGram::declension(10, $words), 'Некорректное склонение в родительный падеж');

		$this->assertEquals('рубля', RusGram::declension(2, $words, true), 'Некорректный вывод без цифры');
	}

	/**
	 * Дата по-русски
	 */
	public function testGetRussianDate() {
		$this->assertEquals('11 Окт октябрь Вс', RusGram::getRussianDate('d M FI D', '2015-10-11'), 'Некорректное формирование даты');
		$this->assertEquals('11 октября Воскресенье', RusGram::getRussianDate('d FR l', '2015-10-11'), 'Некорректное формирование даты');
	}
}
