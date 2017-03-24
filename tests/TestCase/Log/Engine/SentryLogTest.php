<?php
namespace ArtSkills\Test\TestCase\Log\Engine;

use ArtSkills\Log\Engine\SentryLog;
use ArtSkills\TestSuite\Mock\MethodMocker;
use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\PermanentMocks\MockFileLog;
use Cake\Core\Configure;
use Cake\Log\Engine\FileLog;
use Cake\Log\Log;

class SentryLogTest extends AppTestCase
{
	const TEST_SENTRY_DSN = 'https://testHash5:testHash6@app.getsentry.com/7890';
	const SENTRY_CONFIG_NAME = 'sentry';

	/** @inheritdoc */
	public function setUp() {
		$this->_disablePermanentMock(MockFileLog::class);
		parent::setUp();
	}

	/** @inheritdoc */
	public function tearDown() {
		parent::tearDown();
		$this->_dropLog();
	}

	/**
	 * Удалить лог из конфига логов
	 */
	private function _dropLog() {
		if (in_array(self::SENTRY_CONFIG_NAME, Log::configured())) {
			Log::drop(self::SENTRY_CONFIG_NAME);
		}
	}

	/**
	 * Не заданы обызательные параметры
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Empty "sentryDsn" parameter in config
	 */
	public function testEmptyDsn() {
		Configure::delete(SentryLog::CONFIG_DSN_NAME);
		new SentryLog();
	}

	/**
	 * Инициализация конфигурации логера
	 */
	private function _initConfig() {
		Configure::write(SentryLog::CONFIG_DSN_NAME, self::TEST_SENTRY_DSN);
		$this->_dropLog();

		Log::config(self::SENTRY_CONFIG_NAME, [
			'className' => 'ArtSkills.Sentry',
			'levels' => ['error'],
		]);
	}

	/**
	 * Exception отправляется в Sentry
	 */
	public function testNotSendException() {
		$this->_initConfig();
		$testMsg = '[Exception] blablabla';
		MethodMocker::mock(\Raven_Client::class, 'captureMessage')->expectCall(0);
		MethodMocker::mock(FileLog::class, 'log')->singleCall();
		Log::error($testMsg);
	}

	/**
	 * Явная отправка сообщения в Sentry
	 */
	public function testSendError() {
		$this->_initConfig();

		$testMsg = 'blablabla';

		MethodMocker::mock(\Raven_Client::class, 'captureMessage')
			->singleCall()
			->willReturnAction(function ($args) use ($testMsg) {
				self::assertContains($testMsg, $args[0]);
				self::assertContains('Trace', $args[0]);
			});
		MethodMocker::mock(FileLog::class, 'log')->singleCall();

		Log::error($testMsg);
	}
}
