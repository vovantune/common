<?php
namespace ArtSkills\Test\TestCase\TestSuite\HttpClientMock;

use ArtSkills\Http\Client;
use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\HttpClientMock\HttpClientAdapter;
use ArtSkills\TestSuite\HttpClientMock\HttpClientMocker;
use Cake\Http\Client\Request;
use Cake\Http\Client\Response;

class HttpClientMockerTest extends AppTestCase
{
	/**
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		HttpClientMocker::clean();
		HttpClientAdapter::disableDebug();
	}

	/**
	 * Мок запроса
	 */
	public function testMock() {
		$url = 'http://www.artskills.ru';
		$method = Request::METHOD_POST;
		$post = ['foo' => 'bar'];
		$returnArray = [
			'arr' => 1,
			2 => 3,
		];

		$mock = HttpClientMocker::mock($url, $method);
		$mock->singleCall()
			->expectBody($post)
			->willReturnJson($returnArray);

		$client = new Client();
		self::assertNotEmpty($client->get($url)->body);
		self::assertEquals($returnArray, $client->post($url, $post)->json);
	}

	/**
	 * Тест снифера запросов
	 */
	public function testSynteticSniff() {
		$testArray = ['request' => 'request', 'response' => 'response'];

		HttpClientMocker::addSniff($testArray);

		$resultCollection = HttpClientMocker::getSniffList();
		self::assertCount(1, $resultCollection);
		self::assertEquals($testArray, $resultCollection[0]);

		HttpClientMocker::clean();
		self::assertCount(0, HttpClientMocker::getSniffList());
	}

	/**
	 * Тесто полного цикла снифа
	 */
	public function testRealSniff() {
		$url = 'http://www.artskills.ru';
		$client = new Client();
		$clientResponse = $client->get($url);

		$sniffCollection = HttpClientMocker::getSniffList();
		self::assertCount(1, $sniffCollection);
		/**
		 * @var Request $sniffRequest
		 */
		$sniffRequest = $sniffCollection[0]['request'];
		/**
		 * @var Response $sniffResponse
		 */
		$sniffResponse = $sniffCollection[0]['response'];
		self::assertEquals($url, $sniffRequest->getUri());
		self::assertEquals($clientResponse->body, $sniffResponse->body);
	}
}