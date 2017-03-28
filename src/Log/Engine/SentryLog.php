<?php
namespace ArtSkills\Log\Engine;

use ArtSkills\Lib\Env;
use Cake\Log\Engine\BaseLog;
use \Raven_Client;

class SentryLog extends BaseLog
{
	const SKIP_MESSAGE_TEXTS = ['Fatal Error', 'Exception', 'Notice'];
	const BACKTRACE_LIMIT = 5;

	const CONFIG_DSN_NAME = 'sentryDsn';

	/**
	 * Строка доступа
	 *
	 * @var mixed|string
	 */
	private $_dsn = '';

	/**
	 * @inheritdoc
	 */
	public function __construct($options = []) {
		if (!Env::hasSentryDsn()) {
			throw new \Exception('Empty "sentryDsn" parameter in config!');
		}

		$this->_dsn = Env::getSentryDsn();
		parent::__construct($options);
	}

	/**
	 * @inheritdoc
	 */
	public function log($level, $message, array $context = []) {
		if (empty($this->_dsn)) {
			return;
		}

		foreach (self::SKIP_MESSAGE_TEXTS as $skipMsg) {
			if (strstr($message, $skipMsg)) {
				return;
			}
		}

		$traceArr = ["\nTrace:"];

		$calledFrom = debug_backtrace();
		for ($i = 0; $i < self::BACKTRACE_LIMIT; $i++) {
			if (!empty($calledFrom[$i])) {
				$traceArr[] = (!empty($calledFrom[$i]['class']) ? $calledFrom[$i]['class'] . '::' : '') .
					$calledFrom[$i]['function'] .
					(!empty($calledFrom[$i]['file']) ? str_replace(ROOT, '', ' in file ' . $calledFrom[$i]['file']) . '(' . $calledFrom[$i]['line'] . ")" : '');
			}
		}

		$message .= implode("\n", $traceArr);
		$client = new Raven_Client($this->_dsn, Env::getSentryOptions() ?: []);
		$client->captureMessage($message);
	}
}
