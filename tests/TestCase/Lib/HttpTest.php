<?php

namespace ArtSkills\Test\TestCase\Lib;

use ArtSkills\Http\Client;
use ArtSkills\Lib\Http;
use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\HttpClientMock\HttpClientMocker;
use ArtSkills\TestSuite\Mock\MethodMocker;
use Cake\Http\Client\Request;

class HttpTest extends AppTestCase
{


    /**
     * Проверка работы Json
     *
     * @throws \Exception
     */
    public function testGetJson(): void
    {
        $testJson = ['thisIs' => 'Json test'];
        HttpClientMocker::mock('http://testapi.com', Request::METHOD_GET)
            ->singleCall()
            ->willReturnJson($testJson);
        self::assertEquals($testJson, Http::getJson('http://testapi.com'), 'Результаты запроса не совпадают');

        // запрос обломался
        MethodMocker::mock(Http::class, '_getRequest')
            ->willReturnValue(null);

        self::assertNull(Http::getJson('http://testapi.com'));
    }

    /**
     * Проверка работы получения строки
     *
     * @throws \Exception
     */
    public function testGetContent(): void
    {
        $testString = 'lala';
        HttpClientMocker::mock('http://testapi.com', Request::METHOD_GET)
            ->singleCall()
            ->willReturnString($testString);
        self::assertEquals($testString, Http::getContent('http://testapi.com'), 'Результаты запроса не совпадают');

        // запрос обломался
        MethodMocker::mock(Http::class, '_getRequest')
            ->willReturnValue(null);

        self::assertNull(Http::getContent('http://testapi.com'));
    }

    /**
     * Проверка работы получения строки
     *
     * @throws \Exception
     */
    public function testPostContent(): void
    {
        $testJson = ['thisIs' => 'Json test'];
        $testData = ['testData' => '123'];

        HttpClientMocker::mockPost('http://testapi.com', $testData)
            ->singleCall()
            ->willReturnJson($testJson);
        self::assertEquals($testJson, Http::postJson('http://testapi.com', $testData), 'Результаты запроса не совпадают');

        // запрос обломался
        MethodMocker::mock(\Cake\Http\Client::class, 'post')
            ->willReturnValue(null);

        self::assertNull(Http::postJson('http://testapi.com', $testData));
    }

    /**
     * Проверка работы получения строки
     *
     * @throws \Exception
     */
    public function testPutContent(): void
    {
        $testJson = ['thisIs' => 'Json test'];
        $testData = ['testData' => '123'];

        HttpClientMocker::mock('http://testapi.com', Request::METHOD_PUT)
            ->singleCall()
            ->expectBody($testData)
            ->willReturnJson($testJson);
        self::assertEquals($testJson, Http::putJson('http://testapi.com', $testData), 'Результаты запроса не совпадают');

        // запрос обломался
        MethodMocker::mock(\Cake\Http\Client::class, 'put')
            ->willReturnValue(null);

        self::assertNull(Http::putJson('http://testapi.com', $testData));
    }


    /**
     * Проверка работы получения строки
     *
     * @throws \Exception
     */
    public function testGetXml(): void
    {
        $testXml = '<?xml version="1.0" encoding="utf-8"?>
					<!DOCTYPE recipe>
					<recipe name="хлеб" preptime="5min" cooktime="180min">
					   <title>
						  Сладкий хлеб
					   </title>
					   <composition>
						  <ingredient amount="3" unit="стакан">Мука</ingredient>
						  <ingredient amount="0.25" unit="грамм">Дрожжи</ingredient>
						  <ingredient amount="1.5" unit="стакан">Вода</ingredient>
					   </composition>
					   <instructions>
						 <step>
							Смешать все ингредиенты и тщательно замесить.
						 </step>
						 <step>
							Закрыть тканью и оставить на один час в тёплом помещении.
						 </step>
						 <!--
							<step>
							   Почитать вчерашнюю газету.
							</step>
							 - это сомнительный шаг...
						  -->
						 <step>
							Замесить ещё раз, положить на противень и поставить в духовку.
						 </step>
					   </instructions>
					</recipe>';
        HttpClientMocker::mock('http://testapi.com', Request::METHOD_GET)
            ->singleCall()
            ->willReturnString($testXml);

        $ipDataXml = simplexml_load_string($testXml);
        self::assertEquals(
            $ipDataXml->asXML(),
            Http::getXml('http://testapi.com')->asXML(),
            'Результаты запроса не совпадают'
        );

        // запрос обломался
        MethodMocker::mock(Http::class, '_getRequest')
            ->willReturnValue(null);

        self::assertNull(Http::getXml('http://testapi.com'));
    }

    /** POST xml запрос */
    public function testPostXML(): void
    {
        $testXml = '<?xml version="1.0" encoding="utf-8"?>
					<!DOCTYPE recipe>
					<recipe name="хлеб" preptime="5min" cooktime="180min">
					   <title>
						  Сладкий хлеб
					   </title>
					   <composition>
						  <ingredient amount="3" unit="стакан">Мука</ingredient>
						  <ingredient amount="0.25" unit="грамм">Дрожжи</ingredient>
						  <ingredient amount="1.5" unit="стакан">Вода</ingredient>
					   </composition>
					   <instructions>
						 <step>
							Смешать все ингредиенты и тщательно замесить.
						 </step>
						 <step>
							Закрыть тканью и оставить на один час в тёплом помещении.
						 </step>
						 <!--
							<step>
							   Почитать вчерашнюю газету.
							</step>
							 - это сомнительный шаг...
						  -->
						 <step>
							Замесить ещё раз, положить на противень и поставить в духовку.
						 </step>
					   </instructions>
					</recipe>';

        $expArgs = [
            'arg1' => 1,
            'arg2' => 'two',
        ];

        HttpClientMocker::mock('http://testapi.com', Request::METHOD_POST)
            ->singleCall()
            ->expectBody($expArgs)
            ->willReturnString($testXml);

        $ipDataXml = simplexml_load_string($testXml);
        self::assertEquals(
            $ipDataXml->asXML(),
            Http::postXml('http://testapi.com', $expArgs)->asXML(),
            'Результаты запроса не совпадают'
        );
    }

    /**
     * Проверяем загрузку на сесуществующем пути
     */
    public function testDownloadError(): void
    {
        $file = Http::downloadFile('file:///Такого пути не существует');
        self::assertEmpty($file, 'Я сказал не существует!');
    }

    /**
     * Проверяем попытку записи в никуда
     */
    public function testWriteError(): void
    {
        $file = Http::downloadFile('file:///' . __FILE__, '/404Folder/file');
        self::assertEmpty($file, 'Этот файл не должен был появится');
    }
}
