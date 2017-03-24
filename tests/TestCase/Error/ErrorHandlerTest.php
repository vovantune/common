<?php
namespace ArtSkills\Test\TestCase\Error\AppConsoleErrorHandlerTest;

use ArtSkills\Error\ErrorHandler;
use ArtSkills\Log\Engine\SentryLog;
use ArtSkills\TestSuite\Mock\MethodMocker;
use ArtSkills\TestSuite\AppTestCase;
use Cake\Core\Configure;
use Cake\Error\BaseErrorHandler;
use \Raven_Client;
use Cake\Error\FatalErrorException;
use \Exception;

class ErrorHandlerTest extends AppTestCase
{
	const TEST_CONFIG = 'https://testHash3:testHash4@sentry.io/45678';

	/**
	 * handler
	 *
	 * @var ErrorHandler
	 */
	private $_handler = null;

	/** @inheritdoc */
	public function setUp() {
		parent::setUp();
		MethodMocker::mock(BaseErrorHandler::class, 'register');
		$this->_handler = new ErrorHandler();

	}

	/**
	 * Без настроеной конфигурации не должно быть записи в лог
	 */
	public function testNoConfig() {
		MethodMocker::mock(BaseErrorHandler::class, '_logException');
		MethodMocker::mock(Raven_Client::class, 'captureException')
			->expectCall(0);

		$this->_handler->register();
		MethodMocker::callPrivate($this->_handler, '_logException', [new \Exception('тест')]);
	}

	/**
	 * Базовый набор моков
	 */
	private function _initHandler() {
		Configure::write(SentryLog::CONFIG_DSN_NAME, self::TEST_CONFIG);
		$this->_handler->register();
	}

	/**
	 * Тест записи Exception
	 */
	public function testException() {
		$this->_initHandler();

		MethodMocker::mock(BaseErrorHandler::class, '_logException');
		MethodMocker::mock(Raven_Client::class, 'captureException')
			->singleCall();

		MethodMocker::callPrivate($this->_handler, '_logException', [new Exception('тест')]);
	}

	/**
	 * Данные в Sentry отправляются, а в лог не пишутся
	 */
	public function testExceptionWithoutLog() {
		$this->_initHandler();

		MethodMocker::mock(BaseErrorHandler::class, '_logException')
			->expectCall(0);
		MethodMocker::mock(Raven_Client::class, 'captureException')
			->expectCall(0);

		MethodMocker::callPrivate($this->_handler, '_logException', [new FatalErrorException('тест')]);
	}

	/**
	 * Отправка сообщения об ошибке
	 */
	public function testError() {
		$this->_initHandler();
		MethodMocker::mock(BaseErrorHandler::class, '_logError');
		MethodMocker::mock(Raven_Client::class, 'captureException')
			->singleCall();

		MethodMocker::callPrivate($this->_handler, '_logError',
			[
				'error',
				[
					'description' => 'test',
					'code' => 500,
					'file' => __FILE__,
					'line' => __LINE__,
				],
			]);
	}
}