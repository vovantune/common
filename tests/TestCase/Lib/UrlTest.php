<?php
namespace ArtSkills\Test\TestCase\Lib;

use ArtSkills\Lib\Url;
use ArtSkills\TestSuite\AppTestCase;

class UrlTest extends AppTestCase
{
	/** простые методы */
	public function test() {
		self::assertEquals('common.artskills.ru', Url::domain());
		self::assertEquals('http', Url::protocol());
		self::assertEquals('asd/qwe/fgh', Url::path(['asd', 'qwe', 'fgh']));
	}

	/** генерация урла */
	public function testGenerate() {
		self::assertEquals('http://common.artskills.ru', Url::withDomainAndProtocol());
		self::assertEquals('http://common.artskills.ru/asd/fgh?asd=fgh&zxc=vbn', Url::withDomainAndProtocol('/asd/fgh', '?asd=fgh&zxc=vbn'));
		self::assertEquals('http://common.artskills.ru/qwe/rty?qwe=rty&ghj=uio', Url::withDomainAndProtocol(['qwe', 'rty'], ['qwe' => 'rty', 'ghj' => 'uio']));
	}
}
