<?php
namespace ArtSkills\Test\TestCase\Error\AppConsoleErrorHandlerTest;

use ArtSkills\Error\ConsoleErrorHandler;
use ArtSkills\Log\Engine\SentryLog;
use ArtSkills\TestSuite\Mock\MethodMocker;
use ArtSkills\TestSuite\AppTestCase;
use Cake\Core\Configure;
use Cake\Error\BaseErrorHandler;
use \Raven_Client;
use Cake\Error\FatalErrorException;
use \Exception;

class ConsoleErrorHandlerTest extends AppTestCase
{
	const TEST_DSN = 'https://testHash1:testHash2@app.getsentry.com/12345';

	/**
	 * handler
	 *
	 * @var ConsoleErrorHandler
	 */
	private $_handler = null;

	/** @inheritdoc */
	public function setUp() {
		parent::setUp();
		MethodMocker::mock(ConsoleErrorHandler::class, '_initHandlers');
		Configure::write(SentryLog::CONFIG_DSN_NAME, self::TEST_DSN);

		$this->_handler = new ConsoleErrorHandler();
		$this->_handler->register();
	}

	/** @inheritdoc */
	public function tearDown() {
		parent::tearDown();
		Configure::delete(SentryLog::CONFIG_DSN_NAME);
	}

	/**
	 * Тест записи Exception
	 */
	public function testException() {
		MethodMocker::mock(BaseErrorHandler::class, '_logException');
		MethodMocker::mock(Raven_Client::class, 'captureException')
			->singleCall();

		MethodMocker::callPrivate($this->_handler, '_logException', [new Exception('тест')]);
	}

	/**
	 * Данные в Sentry отправляются, а в лог не пишутся
	 */
	public function testExceptionWithoutLog() {
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

	/**
	 * Обработка excpetion с выводом на экран
	 */
	public function handleExceptionWithDisplay() {
		MethodMocker::mock(ConsoleErrorHandler::class, '_logException');
		MethodMocker::mock(ConsoleErrorHandler::class, '_displayException');
		MethodMocker::mock(ConsoleErrorHandler::class, '_stop');

		$this->_handler->handleException(new Exception('test'));
	}

	/**
	 * Обработка excpetion с выводом на экран
	 */
	public function handleExceptionWithoutDisplay() {
		MethodMocker::mock(ConsoleErrorHandler::class, '_logException');
		MethodMocker::mock(ConsoleErrorHandler::class, '_displayException')->expectCall(0);
		MethodMocker::mock(ConsoleErrorHandler::class, '_stop');

		$this->_handler->handleException(new FatalErrorException('test'));
	}
}