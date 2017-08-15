<?php
namespace ArtSkills\Test\TestCase\TestSuite\HttpClientMock;

use ArtSkills\Http\Client;
use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\HttpClientMock\HttpClientAdapter;
use ArtSkills\TestSuite\HttpClientMock\HttpClientMocker;
use ArtSkills\TestSuite\HttpClientMock\HttpClientMockerEntity;
use Cake\Http\Client\Request;
use Cake\Http\Client\Response;

class HttpClientMockerTest extends AppTestCase
{
	/** @inheritdoc */
	public function setUp() {
		parent::setUp();
		HttpClientMocker::clean();
	}

	/** Мок запроса */
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
		self::assertEquals($returnArray, $client->post($url, $post)->json);
		HttpClientAdapter::disableDebug();
		self::assertNotEmpty($client->get($url)->body);
	}

	/** Тест снифера запросов */
	public function testSyntheticSniff() {
		$testArray = ['request' => 'request', 'response' => 'response'];

		HttpClientMocker::addSniff($testArray);

		$resultCollection = HttpClientMocker::getSniffList();
		self::assertCount(1, $resultCollection);
		self::assertEquals($testArray, $resultCollection[0]);

		HttpClientMocker::clean();
		self::assertCount(0, HttpClientMocker::getSniffList());
	}

	/** Тест полного цикла снифа */
	public function testRealSniff() {
		HttpClientAdapter::disableDebug();
		$url = 'http://www.artskills.ru';
		$client = new Client();
		$clientResponse = $client->get($url);

		$sniffCollection = HttpClientMocker::getSniffList();
		self::assertCount(1, $sniffCollection);
		/** @var Request $sniffRequest */
		$sniffRequest = $sniffCollection[0]['request'];
		/** @var Response $sniffResponse */
		$sniffResponse = $sniffCollection[0]['response'];
		self::assertEquals($url, $sniffRequest->getUri());
		self::assertEquals($clientResponse->body, $sniffResponse->body);
	}

	/**
	 * Нельзя замокать одно и то же 2 раза
	 *
	 * @expectedException \PHPUnit\Framework\ExpectationFailedException
	 * @expectedExceptionMessage GET http://www.artskills.ru is already mocked
	 */
	public function testMockTwice() {
		$url = 'http://www.artskills.ru';
		$method = Request::METHOD_GET;

		HttpClientMocker::mock($url, $method)->noCalls();
		HttpClientMocker::mock($url, $method);
	}

	/** Но с разными методами можно замокать 1 урл несколько раз */
	public function testMockTwiceDifferentMethods() {
		$url = 'http://www.artskills.ru';

		HttpClientMocker::mock($url, Request::METHOD_POST)->noCalls();
		HttpClientMocker::mock($url, Request::METHOD_GET)->noCalls();
		self::assertTrue(true, 'Не кинулся ексепшн');
	}

	/** Мок возвращает код статуса */
	public function testStatusCode() {
		$url = 'http://www.artskills.ru';
		$mock = HttpClientMocker::mock($url, Request::METHOD_GET);
		$client = new Client();

		$responseBody = 'test body';
		$statusCode = 526;
		$mock->willReturnString($responseBody)->willReturnStatus($statusCode);
		$response = $client->get($url);
		self::assertEquals($responseBody, (string)$response->getBody());
		self::assertEquals($statusCode, $response->getStatusCode());

		$statusCode = 100;
		$mock->willReturnStatus($statusCode);
		$response = $client->get($url);
		self::assertEquals($statusCode, $response->getStatusCode());

		$responses = ['resp1', 'resp2'];
		$codes = [200, 404];
		$mock->willReturnAction(function ($request, $mock) use ($responses, $codes) {
			/** @var HttpClientMockerEntity $mock */
			static $i = -1;
			$i++;
			$mock->willReturnStatus($codes[$i]);
			return $responses[$i];
		});

		$response = $client->get($url);
		self::assertEquals($responses[0], (string)$response->getBody());
		self::assertEquals($codes[0], $response->getStatusCode());

		$response = $client->get($url);
		self::assertEquals($responses[1], (string)$response->getBody());
		self::assertEquals($codes[1], $response->getStatusCode());
	}

	/** тест метода mockGet */
	public function testMockGet() {
		$url = 'http://www.artskills.ru';
		$data = ['foo' => 'bar'];
		$responseBody = 'test response';
		HttpClientMocker::mockGet($url, $data)->willReturnString($responseBody);

		$client = new Client();
		self::assertEquals($responseBody, (string)$client->get($url, $data)->getBody());
	}

	/** тест метода mockGet, когда в урле уже были гет-параметры */
	public function testMockGetAppend() {
		$url = 'http://www.artskills.ru';
		$data = ['foo' => 'bar'];
		$prevData = ['asd' => 'qwe'];
		$responseBody = 'new test response';
		HttpClientMocker::mockGet($url . '?' . http_build_query($prevData), $data)
			->willReturnString($responseBody);

		$client = new Client();
		self::assertEquals($responseBody, (string)$client->get($url, $prevData + $data)->getBody());
	}

	/** тест метода mockPost */
	public function testMockPost() {
		$url = 'http://www.artskills.ru';
		$data = ['foo' => 'bar'];
		$responseBody = 'post test response';
		HttpClientMocker::mockPost($url, $data)->willReturnString($responseBody);

		$client = new Client();
		self::assertEquals($responseBody, (string)$client->post($url, $data)->getBody());
	}

	/**
	 * тест метода mockPost, запрос с неожиданным body
	 *
	 * @expectedException \PHPUnit\Framework\ExpectationFailedException
	 * @expectedExceptionMessage Expected POST body data is not equal to real data
	 */
	public function testMockPostUnexpectedBody() {
		$url = 'http://www.artskills.ru';
		HttpClientMocker::mockPost($url, ['foo' => 'bar'])->willReturnString('');

		$client = new Client();
		$client->post($url, ['asd' => 'qwe'])->getBody();
	}

}