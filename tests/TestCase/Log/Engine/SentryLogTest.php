<?php
namespace ArtSkills\Test\TestCase\Log\Engine;

use ArtSkills\Log\Engine\SentryLog;
use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\Mock\MethodMocker;
use ArtSkills\TestSuite\Mock\MethodMockerEntity;
use ArtSkills\TestSuite\Mock\PropertyAccess;
use ArtSkills\TestSuite\PermanentMocks\MockFileLog;
use Cake\Console\ConsoleErrorHandler;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Error\Debugger;
use Cake\Log\Engine\FileLog;
use Cake\Log\Log;
use Cake\Network\Exception\NotFoundException;

class SentryLogTest extends AppTestCase
{

	// ВАЖНО!!!!
	// в конфиге логов обязательно использовать ArtSkills.File и ArtSkills.Sentry
	// и для тестов отключить default_breadcrumb_handlers

	// Не получится написать тесты на:
	// непойманные ексепшны
	// вызов shutdown

	const CONTEXT_DEFAULT = ['scope' => []];

	/**
	 * мок
	 *
	 * @var MethodMockerEntity
	 */
	private $_fileLogMock = null;

	/**
	 * мок
	 *
	 * @var MethodMockerEntity
	 */
	private $_sentryLogMock = null;


	/** @inheritdoc */
	public function setUp() {
		$this->_disablePermanentMock(MockFileLog::class);
		parent::setUp();
		$breadcrumbs = SentryLog::getSentry()->breadcrumbs;
		PropertyAccess::set($breadcrumbs, 'count', 0);
		PropertyAccess::set($breadcrumbs, 'pos', 0);
		PropertyAccess::set($breadcrumbs, 'buffer', []);
		PropertyAccess::setStatic(SentryLog::class, '_addInfo', []);

		$this->_fileLogMock = MethodMocker::mock(FileLog::class, 'log');
		$this->_sentryLogMock = MethodMocker::mock(\Raven_Client::class, 'capture')
			->willReturnAction(
				function ($args, $info) {
					// проверяем уровень, сообщение, отпечаток и вершину стек-трейса
					$level = empty($info['level']) ? \Raven_Client::ERROR : $info['level'];
					self::assertEquals($level, $args[0]['level']);

					$isException = !empty($args[0]['exception']);
					if (empty($info['isException'])) {
						self::assertFalse($isException, 'Не ожидался ексепшн');
					} else {
						self::assertTrue($isException, 'Ожидался ексепшн, а на самом деле - обычная запись');
					}
					if ($isException) {
						$actualMessage = $args[0]['exception']['values'][0]['value'];
					} else {
						$actualMessage = $args[0]['message'];
					}
					if (!empty($info['messageLike'])) {
						self::assertContains($info['messageLike'], $actualMessage);
					} else {
						self::assertEquals($info['message'], $actualMessage);
					}

					if (!empty($info['fingerprint'])) {
						self::assertEquals($info['fingerprint'], $args[0]['fingerprint']);
					}

					$traceLast = array_shift($args[1]);
					$expectedTraceLast = [
						'file' => __FILE__,
					];
					$expectedTraceLast = (empty($info['trace']) ? [] : $info['trace']) + $expectedTraceLast;
					$this->assertArraySubsetEquals($expectedTraceLast, $traceLast);
				}
			);
	}

	/** @inheritdoc */
	public function tearDown() {
		parent::tearDown();
		PropertyAccess::setStatic(SentryLog::class, '_isShutdown', false);
	}

	/** простой вызов Log::error */
	public function testLogError() {
		$message = 'test message 1';
		$this->_fileLogMock->singleCall()->expectArgs('error', $message, self::CONTEXT_DEFAULT);
		$this->_sentryLogMock->singleCall()->setAdditionalVar(
			[
				'message' => $message,
				'trace' => [
					'class' => Log::class,
					'function' => 'error',
				],
			]
		);
		Log::error($message);
	}

	/** вызов Log::write (смотрим, что правильный трейс) */
	public function testLogWrite() {
		$levelError = 'error';
		$message = 'test message 2';
		$this->_fileLogMock->singleCall()->expectArgs($levelError, $message, self::CONTEXT_DEFAULT);
		$this->_sentryLogMock->singleCall()->setAdditionalVar(
			[
				'message' => $message,
				'trace' => [
					'class' => Log::class,
					'function' => 'write',
				],
			]
		);
		Log::write($levelError, $message);
	}

	/** вызов Log::warning (смотрим, что другой level) */
	public function testLogWarn() {
		$message = 'test message 3';
		$this->_fileLogMock->singleCall()->expectArgs('warning', $message, self::CONTEXT_DEFAULT);
		$this->_sentryLogMock->singleCall()->setAdditionalVar(
			[
				'level' => \Raven_Client::WARNING,
				'message' => $message,
				'trace' => [
					'class' => Log::class,
					'function' => 'warning',
				],
			]
		);
		Log::warning($message);
	}

	/** вызов Log::info (не пошлётся в сенттри) */
	public function testLogInfo() {
		$message = 'test message 4';
		$this->_fileLogMock->singleCall()->expectArgs('info', $message, self::CONTEXT_DEFAULT);
		$this->_sentryLogMock->expectCall(0);
		Log::info($message);
	}

	/** вызов Log::info с отсылкой в сентри */
	public function testLogInfoSend() {
		$paramSentrySend = [SentryLog::KEY_SENTRY_SEND => true];
		$message = 'test message 5';
		$this->_fileLogMock->singleCall()->expectArgs('info', $message, self::CONTEXT_DEFAULT + $paramSentrySend);
		$this->_sentryLogMock->singleCall()->setAdditionalVar(
			[
				'level' => \Raven_Client::INFO,
				'message' => $message,
				'trace' => [
					'class' => Log::class,
					'function' => 'info',
				],
			]
		);
		Log::info($message, $paramSentrySend);
	}

	/** передача доп. параметров */
	public function testAddData() {
		$addData = [
			'asd' => 'qwe',
			'zxc' => [
				'zxc' => [
					'fgh',
				],
			],
		];
		$addDataParam = [SentryLog::KEY_ADD_INFO => $addData];
		$addDataString = Debugger::exportVar($addData);
		$message = 'test message 6';
		$this->_fileLogMock->singleCall()->expectArgs(
			'error', $message . "\n" . $addDataString, self::CONTEXT_DEFAULT + $addDataParam
		);
		$this->_sentryLogMock->singleCall()->setAdditionalVar(
			[
				'message' => $message,
				'trace' => [
					'class' => Log::class,
					'function' => 'error',
				],
			]
		);
		Log::error($message, $addDataParam);
		$sentryExtraContext = SentryLog::getSentry()->context->extra;
		$this->assertArraySubsetEquals(['_extra_as_string' => $addDataString], $sentryExtraContext);
	}

	/** передача отпечатка */
	public function testFingerprint() {
		$fingerprint = ['test', 'finger', 'print'];
		$fingerprintParam = [SentryLog::KEY_FINGERPRINT => $fingerprint];
		$message = 'test message 7';
		$this->_fileLogMock->singleCall()->expectArgs('error', $message, self::CONTEXT_DEFAULT + $fingerprintParam);
		$this->_sentryLogMock->singleCall()->setAdditionalVar(
			[
				'message' => $message,
				'trace' => [
					'class' => Log::class,
					'function' => 'error',
				],
				'fingerprint' => $fingerprint,
			]
		);
		Log::error($message, $fingerprintParam);
	}

	/** параметр "обработано" - не слать в сентри */
	public function testIsHandled() {
		$paramIsHandled = [SentryLog::KEY_IS_HANDLED => true];
		$message = 'test message 8';
		$this->_fileLogMock->singleCall()->expectArgs('error', $message, self::CONTEXT_DEFAULT + $paramIsHandled);
		$this->_sentryLogMock->expectCall(0);
		Log::error($message, $paramIsHandled);
	}

	/** логирование ексепшнов */
	public function testLogException() {
		$message = 'test message 9';
		$this->_fileLogMock->singleCall()->expectArgs(
			'error', $message, self::CONTEXT_DEFAULT + [SentryLog::KEY_IS_HANDLED => true]
		);
		$this->_sentryLogMock->singleCall()->setAdditionalVar(['message' => $message, 'isException' => true]);
		SentryLog::logException(new \Exception($message));
	}

	/** логирование неинтересных ексепшнов */
	public function testLogExceptionWarning() {
		$message = 'test message 9.1';
		$this->_fileLogMock->singleCall()->expectArgs(
			'warning', $message, self::CONTEXT_DEFAULT + [SentryLog::KEY_IS_HANDLED => true]
		);
		$this->_sentryLogMock->singleCall()->setAdditionalVar([
			'message' => $message,
			'isException' => true,
			'level' => \Raven_Client::WARN,
		]);
		SentryLog::logException(new NotFoundException($message));
	}


	/** ошибки */
	public function testTriggerError() {
		$message = 'test message 10';
		$logMessageLike = 'Fatal Error (256): ' . $message;
		$this->_fileLogMock->singleCall()->willReturnAction(
			function ($args) use ($logMessageLike) {
				self::assertEquals('error', $args[0]);
				self::assertContains($logMessageLike, $args[1]);
				self::assertEquals(self::CONTEXT_DEFAULT, $args[2]);
			}
		);
		$this->_sentryLogMock->singleCall()->setAdditionalVar(
			[
				'messageLike' => $logMessageLike,
				'trace' => [
					'function' => 'trigger_error',
				],
			]
		);
		$this->_disableFatal();
		trigger_error($message, E_USER_ERROR);
	}

	/** сделать, чтобы тесты не валились из-за тестируемой ошибки */
	private function _disableFatal() {
		MethodMocker::mock(ConsoleErrorHandler::class, '_stop')->singleCall(); // если не замокать, тесты остановятся =)
	}

	/** сделать, чтобы тестируемый нотис не мешался */
	private function _disableNotice() {
		MethodMocker::mock(ConsoleErrorHandler::class, '_displayError')->singleCall(); // чтоб нотис не выводился
		MethodMocker::sniff(ConsoleErrorHandler::class, '_stop')->expectCall(0); // а тут не должно вызываться
	}

	/** нотисы шлются в сентри как ошибки */
	public function testTriggerNotice() {
		$message = 'test message 12';
		$logMessageLike = 'Notice (1024): ' . $message;
		$this->_fileLogMock->singleCall()->willReturnAction(
			function ($args) use ($logMessageLike) {
				self::assertEquals('error', $args[0]);
				self::assertContains($logMessageLike, $args[1]);
				self::assertEquals(self::CONTEXT_DEFAULT, $args[2]);
			}
		);
		$this->_sentryLogMock->singleCall()->setAdditionalVar(
			[
				'messageLike' => $logMessageLike,
				'trace' => [
					'function' => 'trigger_error',
				],
			]
		);
		$this->_disableNotice();
		trigger_error($message, E_USER_NOTICE);
	}

	/** нотис */
	public function testNotice() {
		$logMessageLike = 'Notice (8): Undefined variable: b';
		$this->_fileLogMock->singleCall()->willReturnAction(
			function ($args) use ($logMessageLike) {
				self::assertEquals('error', $args[0]);
				self::assertContains($logMessageLike, $args[1]);
				self::assertEquals(self::CONTEXT_DEFAULT, $args[2]);
			}
		);
		$line = 0;
		$this->_sentryLogMock->singleCall()->setAdditionalVar(
			[
				'messageLike' => $logMessageLike,
				'trace' => [
					'line' => &$line,
					'function' => 'handleError',
				],
			]
		);
		$this->_disableNotice();
		$line = __LINE__ + 1; // переменная выше используется по ссылке
		$a = $b[1]; // ошибка
	}

	/** сам шатдаун не получится вызвать, но можно вызвать обработчик */
	public function testShutdownFlag() {
		$testError = [
			'type' => 8,
			'message' => 'test message 13',
			'file' => __FILE__,
			'line' => __LINE__,
		];
		$logMessageLike = "Fatal Error ({$testError['type']}): {$testError['message']}";
		$this->_fileLogMock->singleCall()->willReturnAction(
			function ($args) use ($logMessageLike) {
				self::assertEquals('error', $args[0]);
				self::assertContains($logMessageLike, $args[1]);
				self::assertEquals(self::CONTEXT_DEFAULT, $args[2]);
			}
		);
		$this->_sentryLogMock->singleCall()->setAdditionalVar(
			[
				'messageLike' => $testError['message'],
				'trace' => [
					'file' => $testError['file'],
					'line' => $testError['line'],
				],
				'isException' => true,
			]
		);
		$this->_disableFatal();
		MethodMocker::callPrivate(new \ArtSkills\Error\ConsoleErrorHandler(Configure::read('Error')), '_logShutdown', [$testError]);
	}

	/** вызов лога из лог-трейта */
	public function testLogTrait() {
		$message = 'test message 14';
		$this->_fileLogMock->singleCall()->expectArgs('error', $message, self::CONTEXT_DEFAULT);
		$this->_sentryLogMock->singleCall()->setAdditionalVar(
			[
				'message' => $message,
				'trace' => [
					'class' => Shell::class,
					'function' => 'log',
				],
			]
		);
		$shell = new Shell();
		$shell->log($message);
	}

	/** плохой вызов нативной функции */
	public function testBadCall() {
		$logMessageLike = 'Warning (2): getimagesize';
		$this->_fileLogMock->singleCall()->willReturnAction(
			function ($args) use ($logMessageLike) {
				self::assertEquals('error', $args[0]);
				self::assertContains($logMessageLike, $args[1]);
				self::assertEquals(self::CONTEXT_DEFAULT, $args[2]);
			}
		);
		$line = 0;
		$this->_sentryLogMock->singleCall()->setAdditionalVar(
			[
				'messageLike' => $logMessageLike,
				'trace' => [
					'line' => &$line,
					'function' => 'getimagesize',
				],
			]
		);
		$this->_disableNotice();
		$line = __LINE__ + 1; // переменная выше используется по ссылке
		getimagesize('govno');
	}

	/** Добавление хлебных крошек после ошибок */
	public function testBreadCrumbs() {
		$this->_sentryLogMock->willReturnValue(null);

		$breadCrumbsObj = SentryLog::getSentry()->breadcrumbs;
		self::assertEquals([], $breadCrumbsObj->fetch());

		// запись в лог
		$message = 'test message 15';
		$extra = ['test add info' => 'some value'];
		Log::warning($message, [SentryLog::KEY_ADD_INFO => $extra]);
		$line = __LINE__ - 1;

		$breadCrumbs = $breadCrumbsObj->fetch();
		self::assertCount(1, $breadCrumbs);
		$crumb = $breadCrumbs[0];
		$this->assertArraySubsetEquals([
			'message' => $message,
			'level' => \Raven_Client::WARN,
		], $crumb);
		self::assertEquals(Debugger::exportVar($extra), $crumb['data']['error_extra']);
		$this->assertArraySubsetEquals([
			'file' => __FILE__,
			'line' => $line,
		], $crumb['data']['from']);


		// ексепшн
		$message = 'test message 16';
		$extraNew = ['test info exception' => 'asdfgh'];
		SentryLog::logException(new \Exception($message), [
			SentryLog::KEY_ADD_INFO => $extraNew,
		]);

		$breadCrumbs = $breadCrumbsObj->fetch();
		self::assertCount(2, $breadCrumbs);
		$crumb = $breadCrumbs[1];
		$this->assertArraySubsetEquals([
			'message' => $message,
			'level' => \Raven_Client::ERROR,
		], $crumb);
		self::assertEquals(Debugger::exportVar($extra + $extraNew), $crumb['data']['error_extra']);
		$this->assertArraySubsetEquals([
			'class' => __CLASS__,
			'function' => __FUNCTION__,
		], $crumb['data']['from']);
	}
}
