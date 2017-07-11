<?php
namespace ArtSkills\Test\TestCase\TestSuite\HttpClientMock;

use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\HttpClientMock\HttpClientMockerEntity;
use Cake\Http\Client\Request;

class HttpClientMockerEntityTest extends AppTestCase
{
	/**
	 * @inheritdoc
	 */
	public $fixtures = [];

	/**
	 * Разовый вызов
	 */
	public function testOnce() {
		$testUrl = 'http://www.artskills.ru';
		$testData = ['foo' => 'barr', 'bar' => 'babar'];
		$correctTestData = ['bar' => 'babar', 'foo' => 'barr'];
		$testMethod = Request::METHOD_POST;
		$returnVal = 'test';

		$mock = new HttpClientMockerEntity('id', $testUrl, $testMethod);
		$mock->singleCall()
			->expectBody($testData)
			->willReturnString($returnVal);

		$request = new Request($testUrl, $testMethod);
		$request->body($correctTestData);

		self::assertTrue($mock->check($testUrl, $testMethod));
		self::assertEquals($returnVal, $mock->doAction($request));
		self::assertEquals(1, $mock->getCallCount());
	}

	/**
	 * Несколько раз вызвали с кэлбаком
	 */
	public function testAny() {
		$testUrl = 'http://www.artskills.ru';
		$testMethod = Request::METHOD_GET;
		$returnVal = 'test';

		$mock = new HttpClientMockerEntity('id', $testUrl, $testMethod);
		$mock->anyCall()
			->willReturnAction(function () use ($returnVal) {
				return $returnVal;
			});

		$request = new Request($testUrl, $testMethod);

		self::assertTrue($mock->check($testUrl, $testMethod));
		self::assertEquals($returnVal, $mock->doAction($request));
		self::assertEquals($returnVal, $mock->doAction($request));
		self::assertEquals(2, $mock->getCallCount());
	}

	/**
	 * Защита от вызова несколько раз
	 *
	 * @expectedException \PHPUnit_Framework_ExpectationFailedException
	 */
	public function testSingleCallCheck() {
		$testUrl = 'http://www.artskills.ru';
		$testMethod = Request::METHOD_GET;
		$returnVal = 'test';

		$mock = new HttpClientMockerEntity('id', $testUrl, $testMethod);
		$mock->singleCall()
			->willReturnString($returnVal);

		$request = new Request($testUrl, $testMethod);

		$mock->doAction($request);
		$mock->doAction($request);
	}

	/**
	 * Ни разу не вызвали
	 *
	 * @expectedException \PHPUnit_Framework_ExpectationFailedException
	 */
	public function testNoCallCheck() {
		$testUrl = 'http://www.artskills.ru';
		$testMethod = Request::METHOD_GET;
		$returnVal = 'test';

		$mock = new HttpClientMockerEntity('id', $testUrl, $testMethod);
		$mock->singleCall()
			->willReturnString($returnVal);

		$mock->callCheck();
	}

	/**
	 * Проверка check метода
	 */
	public function testCheck() {
		$testUrl = 'http://www.artskills.ru';
		$testMethod = Request::METHOD_GET;

		$request = new Request($testUrl, $testMethod);

		$mock = new HttpClientMockerEntity('id', $testUrl, $testMethod);
		$mock->willReturnString('123');
		$mock->doAction($request);

		self::assertFalse($mock->check('blabla', $testMethod));
		self::assertFalse($mock->check($testUrl, Request::METHOD_DELETE));
	}

	/**
	 * Не указали возвращаемый результат
	 *
	 * @expectedException \Exception
	 */
	public function testEmptyResultCheck() {
		$testUrl = 'http://www.artskills.ru';
		$testData = ['foo' => 'barr', 'bar' => 'babar'];
		$testMethod = Request::METHOD_POST;

		$request = new Request($testUrl, $testMethod);
		$request->body($testData);

		$mock = new HttpClientMockerEntity('id', $testUrl, $testMethod);
		$mock->expectBody($testData);
		$mock->doAction($request);
	}

	/**
	 * Указали POST данные для GET запроса
	 *
	 * @expectedException \Exception
	 */
	public function testBodySetForGetMethod() {
		$testUrl = 'http://www.artskills.ru';
		$testMethod = Request::METHOD_GET;
		$testData = ['foo' => 'barr', 'bar' => 'babar'];

		$request = new Request($testUrl, $testMethod);
		$request->body($testData);

		$mock = new HttpClientMockerEntity('id', $testUrl, $testMethod);
		$mock->expectBody($testData)
			->willReturnString('1')
			->doAction($request);
	}

	/**
	 * POST без указания Body, но с кэллбэком
	 */
	public function testNoBodyButCallback() {
		$testUrl = 'http://www.artskills.ru';
		$testData = ['foo' => 'barr', 'bar' => 'babar'];
		$testMethod = Request::METHOD_POST;

		$request = new Request($testUrl, $testMethod);
		$request->body($testData);

		$mock = new HttpClientMockerEntity('id', $testUrl, $testMethod);
		$mock->willReturnAction(function($request){
			return '1';
		});
		$mock->doAction($request);
	}
}
