<?php

namespace ArtSkills\Test\Controller\TestControllerTest;

use ArtSkills\Controller\Controller;
use ArtSkills\Error\UserException;
use ArtSkills\Lib\Env;
use ArtSkills\Log\Engine\SentryLog;
use ArtSkills\TestSuite\AppControllerTestCase;
use ArtSkills\TestSuite\Mock\MethodMocker;

class ControllerTest extends AppControllerTestCase
{

	/** @inheritdoc */
	public function tearDown() {
		Env::enableDebug();
		parent::tearDown();
	}

	/** Успешный JSON ответ */
	public function testJsonOk() {
		$this->get('/test/getJsonOk');
		$this->assertJsonOKEquals(['testProperty' => 123]);
	}

	/** Ответ об ошибке */
	public function testJsonError() {
		$this->get('/test/getJsonError');
		$this->assertJsonErrorEquals('Тестовая ошибка', 'Некорректное сообщение об ошибке', ['errorProperty' => 123]);

	}

	/** Пустой JSON ответ */
	public function testEmptyJson() {
		$this->get('/test/getEmptyJson');
		$this->assertJsonOKEquals([]);
	}

	/** ValueObject в качестве результата */
	public function testGetValueObject() {
		$this->get('/test/getValueObjectJson');
		$this->assertJsonOKEquals([
			'testProperty' => 'testData',
		]);
	}

	/** ответ с ошибкой из исключения */
	public function testErrorFromException() {
		$this->get('/test/getJsonException');
		$this->assertJsonErrorEquals('test exception', 'Некорректное сообщение об ошибке', ['someData' => 'test']);
	}

	/**
	 * Если было исключение phpunit
	 * @expectedException \PHPUnit_Framework_AssertionFailedError
	 * @expectedExceptionMessage test unit exception
	 */
	public function testUnitException() {
		$this->get('/test/getJsonExceptionUnit');
	}

	/** Стандартная обработка ошибок, json */
	public function testStandardErrorJson() {
		MethodMocker::mock(SentryLog::class, 'logException')->expectCall(0);
		$this->get('/test/getStandardErrorJson');
		$this->assertJsonErrorEquals('test json message');
	}

	/** Стандартная обработка ошибок, json, немного сконфигурированная обработка */
	public function testStandardErrorJsonConfigured() {
		MethodMocker::mock(SentryLog::class, 'logException')
			->singleCall()
			->willReturnAction(function($args) {
				/** @var UserException $exception */
				$exception = $args[0];
				self::assertEquals('log message', $exception->getMessage());
				self::assertEquals('user message', $exception->getUserMessage());
				self::assertEquals([
					'scope' => [
						(int) 0 => 'some scope'
					],
					'_addInfo' => 'some info'
				], $args[1]);
				self::assertEquals(false, $args[2]);
			});
		$this->get('/test/getStandardErrorJsonConfigured');
		$this->assertJsonErrorEquals('user message');
	}

	/** Стандартная обработка ошибок, html, flash */
	public function testStandardErrorFlash() {
		MethodMocker::mock(SentryLog::class, 'logException')->expectCall(0);
		$this->_initFlashSniff(1);
		$this->get('/test/getStandardErrorFlash');
		$this->assertFlashError('test flash message');
		$this->assertResponseCode(200);
	}

	/** Стандартная обработка ошибок, flash, редирект */
	public function testStandardErrorRedirect() {
		MethodMocker::mock(SentryLog::class, 'logException')->expectCall(0);
		$this->_initFlashSniff(1);
		$this->get('/test/getStandardErrorRedirect');
		$this->assertFlashError('test other flash message');
		$this->assertRedirect('/test/getJsonOk');
	}

	/** Внутренняя ошибка, отдаёт 5хх; в режиме дебага есть сообщение из ексепшна */
	public function testInternalError() {
		MethodMocker::mock(SentryLog::class, 'logException')->singleCall();
		$this->_initFlashSniff(0);
		$this->get('/test/getInternalError');
		$this->assertResponseCode(500);
		$this->assertResponseContains('An Internal Error Has Occurred');
		$this->assertResponseContains('test internal error');
	}

	/** Внутренняя ошибка, отдаёт 5хх; в режиме продакшна сообщения нет */
	public function testInternalErrorProduction() {
		Env::setDebug(false);
		MethodMocker::mock(SentryLog::class, 'logException')->singleCall();
		$this->_initFlashSniff(0);
		$this->get('/test/getInternalError');
		$this->assertResponseCode(500);
		$this->assertResponseContains('An Internal Error Has Occurred');
		$this->assertResponseNotContains('test internal error');
	}


	/** Внутренняя ошибка, отдаёт json, в режиме дебага есть информация об ексепшне */
	public function testInternalErrorJson() {
		MethodMocker::mock(SentryLog::class, 'logException')->singleCall();
		$this->get('/test/getInternalErrorJson');
		$this->assertJsonErrorEquals(
			'test json message',
			'Неожиданный результат внутренней ошибки в формате json в режиме дебага',
			[
				'url' => '/test/getInternalErrorJson',
				'code' => 500,
				'file' => (new \ReflectionClass(Controller::class))->getFileName(),
				'line' => 119,
			],
			500
		);
	}

	/** Внутренняя ошибка, отдаёт json, продакшна сообщения нет */
	public function testInternalErrorJsonProduction() {
		Env::setDebug(false);
		MethodMocker::mock(SentryLog::class, 'logException')->singleCall();
		$this->get('/test/getInternalErrorJson');
		$this->assertJsonErrorEquals(
			'An Internal Error Has Occurred.',
			'Неожиданный результат внутренней ошибки в формате json в режиме продакшна',
			[
				'url' => '/test/getInternalErrorJson',
				'code' => 500,
			],
			500
		);
	}

}