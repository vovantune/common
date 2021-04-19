<?php

namespace ArtSkills\Test\TestCase\Lib;

use ArtSkills\Lib\Url;
use ArtSkills\TestSuite\AppTestCase;

class UrlTest extends AppTestCase
{
    /** простые методы */
    public function test()
    {
        self::assertEquals('common.artskills.ru', Url::domain());
        self::assertEquals('http', Url::protocol());
        self::assertEquals('asd/qwe/fgh', Url::path(['asd', 'qwe', 'fgh']));
        self::assertEquals('http://common.artskills.ru', Url::domainAndProtocol());
        self::assertEquals('qwe=rty&ghj=uio', Url::buildQuery(['qwe' => 'rty', 'ghj' => 'uio']));
        self::assertEquals(['qwe' => 'rty', 'ghj' => 'uio'], Url::parseQuery('qwe=rty&ghj=uio'));
    }

    /** генерация урла */
    public function testGenerate()
    {
        self::assertEquals('http://common.artskills.ru', Url::withDomainAndProtocol());
        self::assertEquals('http://common.artskills.ru/asd/fgh?asd=fgh&zxc=vbn', Url::withDomainAndProtocol('/asd/fgh', '?asd=fgh&zxc=vbn'));
        self::assertEquals('http://common.artskills.ru/qwe/rty?qwe=rty&ghj=uio#lkjh', Url::withDomainAndProtocol([
            'qwe',
            'rty',
        ], ['qwe' => 'rty', 'ghj' => 'uio'], 'lkjh'));

        $allParams = [['qwe', 'rty'], ['qwe' => 'rty', 'ghj' => 'uio'], 'lkjh'];
        self::assertEquals('common.artskills.ru/qwe/rty?qwe=rty&ghj=uio#lkjh', Url::withDomain(...$allParams));
        self::assertEquals('http://common.artskills.ru/qwe/rty?qwe=rty&ghj=uio#lkjh', Url::withDomainHttp(...$allParams));
        self::assertEquals('blabla/qwe/rty?qwe=rty&ghj=uio#lkjh', Url::withCustomDomain('blabla', ...$allParams));
        self::assertEquals('blabla/qwe/rty?qwe=rty&ghj=uio#lkjh', Url::withCustomDomain('blabla/', ...$allParams));
        self::assertEquals('blabla?asd=qwe', Url::withCustomDomain('blabla/', '', ['asd' => 'qwe']));
        self::assertEquals('/qwe/rty?qwe=rty&ghj=uio#lkjh', Url::withoutDomain(...$allParams));
    }
}
