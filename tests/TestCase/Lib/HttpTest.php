<?php
namespace ArtSkills\Test\TestCase\Lib;

use ArtSkills\Lib\Http;
use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\HttpClientMock\HttpClientMocker;
use Cake\Http\Client\Request;

class HttpTest extends AppTestCase
{


	/**
	 * Проверка работы Json
	 *
	 * @throws \Exception
	 */
	function testGetJson() {
		$testJson = ['thisIs' => 'Json test'];
		HttpClientMocker::mock('http://testapi.com', Request::METHOD_GET)
			->willReturnJson($testJson);
		self::assertEquals($testJson, Http::getJson('http://testapi.com'), 'Результаты запроса не совпадают');
	}

	/**
	 * Проверка работы получения строки
	 *
	 * @throws \Exception
	 */
	function testGetContent() {
		$testString = 'lala';
		HttpClientMocker::mock('http://testapi.com', Request::METHOD_GET)
			->willReturnString($testString);
		self::assertEquals($testString, Http::getContent('http://testapi.com'), 'Результаты запроса не совпадают');
	}


	/**
	 * Проверка работы получения строки
	 *
	 * @throws \Exception
	 */
	function testGetXml() {
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
			->willReturnString($testXml);

		$ipDataXml = simplexml_load_string($testXml);
		self::assertEquals($ipDataXml->asXML(), Http::getXml('http://testapi.com')->asXML(), 'Результаты запроса не совпадают');
	}

	/**
	 * Тестируем обычную загрузку файла
	 */
	function testDownloadBasic() {
		$file = Http::downloadFile('file:///' . __FILE__);
		$this->assertFileEquals($file, __FILE__, 'Файлы не совпадают');
		unlink($file);
	}

	/**
	 * Проверяем загрузку на сесуществующем пути
	 */
	function testDownloadError() {
		$file = Http::downloadFile('file:///Такого пути не существует');
		$this->assertEmpty($file, 'Я сказал не существует!');
	}

	/**
	 * Проверяем попытку записи в никуда
	 */
	function testWriteError() {
		$file = Http::downloadFile('file:///' . __FILE__, '/404Folder/file');
		$this->assertEmpty($file, 'Этот файл не должен был появится');
	}
}