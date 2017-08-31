<?php
namespace ArtSkills\Test\TestCase\TestSuite\HttpClientMock;

use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\HttpClientMock\HttpClientMockerEntity;
use Cake\Http\Client\Request;

class HttpClientMockerEntityTest extends AppTestCase
{
	const DEFAULT_TEST_URL = 'http://www.artskills.ru';
	const DEFAULT_POST_DATA = ['foo' => 'barr', 'bar' => 'babar'];

	/**
	 * Разовый вызов
	 */
	public function testOnceAndReturnString() {
		$testUrl = self::DEFAULT_TEST_URL;
		$testData = self::DEFAULT_POST_DATA;
		$correctTestData = ['bar' => 'babar', 'foo' => 'barr'];
		$testMethod = Request::METHOD_POST;
		$returnVal = 'test';

		$mock = new HttpClientMockerEntity($testUrl, $testMethod);
		$mock->singleCall()
			->expectBody($testData)
			->willReturnString($returnVal);

		$request = new Request($testUrl, $testMethod);
		$request->body($correctTestData);

		self::assertTrue($mock->check($testUrl, $testMethod));
		self::assertEquals($returnVal, $mock->doAction($request));
		self::assertEquals(1, $mock->getCallCount());
		self::assertEquals(200, $mock->getReturnStatusCode());
	}

	/**
	 * Несколько раз вызвали с кэлбаком
	 */
	public function testAnyAndReturnAction() {
		$testUrl = self::DEFAULT_TEST_URL;
		$testMethod = Request::METHOD_GET;
		$returnVal = 'test';

		$mock = new HttpClientMockerEntity($testUrl, $testMethod);
		$mock->willReturnAction(function () use ($returnVal) {
				return $returnVal;
			});

		$request = new Request($testUrl, $testMethod);

		self::assertTrue($mock->check($testUrl, $testMethod));
		self::assertEquals($returnVal, $mock->doAction($request));
		self::assertEquals($returnVal, $mock->doAction($request));
		self::assertEquals(2, $mock->getCallCount());
		self::assertEquals(200, $mock->getReturnStatusCode());
	}

	/**
	 * Защита от вызова несколько раз
	 *
	 * @expectedException \PHPUnit\Framework\ExpectationFailedException
	 * @expectedExceptionMessage expected 1 calls, but more appeared
	 */
	public function testSingleCallCheck() {
		$mock = $this->_makeGetMock()
			->singleCall()
			->willReturnString('test');
		$request = $this->_makeGetRequest();

		$mock->doAction($request);
		$mock->doAction($request);
	}

	/**
	 * Ни разу не вызвали
	 *
	 * @expectedException \PHPUnit\Framework\ExpectationFailedException
	 * @expectedExceptionMessage is not called
	 */
	public function testNoCallCheck() {
		$mock = $this->_makeGetMock();
		$mock->callCheck();
	}

	/** Ни разу не вызвали, но так и должно быть */
	public function testExpectedNoCallCheck() {
		$mock = $this->_makeGetMock()->noCalls();
		$mock->callCheck();
		self::assertTrue(true, 'Не кинулся ексепшн');
	}


	/**
	 * Проверка check метода
	 */
	public function testCheck() {
		$testUrl = self::DEFAULT_TEST_URL;
		$testMethod = Request::METHOD_GET;

		$mock = $this->_makeMock($testMethod, $testUrl);
		self::assertFalse($mock->check('blabla', $testMethod));
		self::assertFalse($mock->check($testUrl, Request::METHOD_DELETE));
		self::assertTrue($mock->check($testUrl, $testMethod));
	}

	/**
	 * Не указали возвращаемый результат
	 *
	 * @expectedException \PHPUnit\Framework\ExpectationFailedException
	 * @expectedExceptionMessage Return mock action is not defined
	 */
	public function testEmptyResultCheck() {
		$this->_makeGetMock()->doAction($this->_makeGetRequest());
	}

	/**
	 * Указали POST данные для GET запроса
	 *
	 * @expectedException \PHPUnit\Framework\ExpectationFailedException
	 * @expectedExceptionMessage Body for GET method is not required
	 */
	public function testBodySetForGetMethod() {
		$this->_makeGetMock()->expectBody(self::DEFAULT_POST_DATA);
	}

	/**
	 * Задали плохой код ответа
	 *
	 * @expectedException \PHPUnit\Framework\ExpectationFailedException
	 * @expectedExceptionMessage Status code should be integer between 100 and 599
	 */
	public function testSetStatusCodeBad() {
		$this->_makeGetMock()->willReturnStatus('233asd');
	}

	/**
	 * Задали слишком маленький код ответа
	 *
	 * @expectedException \PHPUnit\Framework\ExpectationFailedException
	 * @expectedExceptionMessage Status code should be integer between 100 and 599
	 */
	public function testSetStatusCodeSmall() {
		$this->_makeGetMock()->willReturnStatus(99);
	}

	/**
	 * Задали слишком большой код ответа
	 *
	 * @expectedException \PHPUnit\Framework\ExpectationFailedException
	 * @expectedExceptionMessage Status code should be integer between 100 and 599
	 */
	public function testSetStatusCodeBig() {
		$this->_makeGetMock()->willReturnStatus(600);
	}


	/**
	 * Ответ - не строка
	 *
	 * @expectedException \PHPUnit\Framework\ExpectationFailedException
	 * @expectedExceptionMessage Invalid response: Array
	 */
	public function testReturnStringBad() {
		$this->_makeGetMock()->willReturnString(['asd' => 'qwe']);
	}

	/**
	 * Ответ - не строка
	 *
	 * @expectedException \PHPUnit\Framework\ExpectationFailedException
	 * @expectedExceptionMessage Invalid response: Array
	 */
	public function testReturnActionBad() {
		$this->_makeGetMock()
			->willReturnAction(function () {
				return ['asd' => 'qwe'];
			})
			->doAction($this->_makeGetRequest());
	}

	/**
	 * Не существующий файл
	 *
	 * @expectedException \PHPUnit\Framework\ExpectationFailedException
	 * @expectedExceptionMessage is not a file
	 */
	public function testReturnFileNotExists() {
		$this->_makeGetMock()->willReturnFile(__DIR__ . DS . 'non_existent_file.txt');
	}

	/**
	 * Указали не файл
	 *
	 * @expectedException \PHPUnit\Framework\ExpectationFailedException
	 * @expectedExceptionMessage is not a file
	 */
	public function testReturnFileIsNotFile() {
		$this->_makeGetMock()->willReturnFile(__DIR__);
	}

	/** содержимое ответа берётся из файла */
	public function testReturnFile() {
		$result = $this->_makeGetMock()
			->willReturnFile(__DIR__ . DS . 'file_response.txt')
			->doAction($this->_makeGetRequest());
		self::assertEquals('response in a file', $result);
	}

	/**
	 * отправился запрос не с тем body,  которым ожидалось
	 *
	 * @expectedException \PHPUnit\Framework\ExpectationFailedException
	 * @expectedExceptionMessage Expected POST body data is not equal to real data
	 */
	public function testUnexpectedBody() {
		$this->_makePostMock(['asd' => 'qwe'])
			->willReturnString('')
			->doAction($this->_makePostRequest(['asd' => 'zxc']));
	}

	/**
	 * Отправился запрос с пустым body.
	 * Будет ошибка, если явно не указать, что ожидался пустой body
	 *
	 * @expectedException \PHPUnit\Framework\ExpectationFailedException
	 * @expectedExceptionMessage Post request with empty body
	 */
	public function testEmptyPost() {
		$this->_makePostMock(null)
			->willReturnString('')
			->doAction($this->_makePostRequest(''));
	}

	/** сравнение содержимого post body, сравнение проходит */
	public function testExpectBodyGood() {
		$mock = $this->_makePostMock()->willReturnString('');

		$request = $this->_makePostRequest(['asd' => 'qwe']);
		$mock->expectBody(['asd' => 'qwe'])->doAction($request);
		$mock->expectBody('asd=qwe')->doAction($request);

		$request = $this->_makePostRequest('{"rty":"fgh"}')->withHeader('content-type', 'application/json');
		$mock->expectBody(['rty' => 'fgh'])->doAction($request);
		$mock->expectBody('{"rty":"fgh"}')->doAction($request);

		// не проверять body
		$mock->expectBody(null)->doAction($request);
		// явно задано, что ожидается пустота
		$mock->expectEmptyBody()->doAction($this->_makePostRequest(''));

		self::assertTrue(true, 'Не выкинулся ексепшн');
	}





	/**
	 * Получить объект Request с методом GET
	 *
	 * @param string $url
	 * @return Request
	 */
	private function _makeGetRequest($url = self::DEFAULT_TEST_URL) {
		return $this->_makeRequest(Request::METHOD_GET, $url);
	}

	/**
	 * Получить объект Request с методом POST
	 *
	 * @param array|string $data
	 * @param string $url
	 * @return Request
	 */
	private function _makePostRequest($data = self::DEFAULT_POST_DATA, $url = self::DEFAULT_TEST_URL) {
		return $this->_makeRequest(Request::METHOD_POST, $url, $data);
	}

	/**
	 * Получить объект Request
	 *
	 * @param string $method
	 * @param string $url
	 * @param array|string $data
	 * @return Request
	 */
	private function _makeRequest($method, $url = self::DEFAULT_TEST_URL, $data = self::DEFAULT_POST_DATA) {
		$request = new Request($url, $method);
		if ($method === Request::METHOD_POST) {
			$request->body($data);
		}
		return $request;
	}

	/**
	 * Получить объект мока запроса GET
	 *
	 * @param string $url
	 * @return HttpClientMockerEntity
	 */
	private function _makeGetMock($url = self::DEFAULT_TEST_URL) {
		return $this->_makeMock(Request::METHOD_GET, $url);
	}

	/**
	 * Получить объект мока запроса POST
	 *
	 * @param array|string $data
	 * @param string $url
	 * @return HttpClientMockerEntity
	 */
	private function _makePostMock($data = self::DEFAULT_POST_DATA, $url = self::DEFAULT_TEST_URL) {
		return $this->_makeMock(Request::METHOD_POST, $url, $data);
	}

	/**
	 * Получить объект мока запроса
	 *
	 * @param string $method
	 * @param string $url
	 * @param array|string $data
	 * @return HttpClientMockerEntity
	 */
	private function _makeMock($method, $url = self::DEFAULT_TEST_URL, $data = self::DEFAULT_POST_DATA) {
		$mock = new HttpClientMockerEntity($url, $method);
		if ($method === Request::METHOD_POST) {
			$mock->expectBody($data);
		}
		return $mock;
	}



}
