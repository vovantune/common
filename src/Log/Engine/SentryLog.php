<?php
namespace ArtSkills\Log\Engine;

use ArtSkills\Lib\Env;
use ArtSkills\Lib\Strings;
use Cake\Error\Debugger;
use Cake\Error\FatalErrorException;
use Cake\Log\Engine\BaseLog;
use Cake\Log\Log;
use Cake\Log\LogTrait;

class SentryLog extends BaseLog
{

	const INFO_MAX_NEST_LEVEL = 5;

	const KEY_ADD_INFO = '_addInfo';
	const KEY_VARS = '_vars';
	const KEY_SENTRY_SEND = '_send';
	const KEY_IS_HANDLED = '_isHandled';
	const KEY_NO_FILE_LOG = '_noFileLog';
	const KEY_TAGS = '_tags';
	/** для кастомной группировки сообщений */
	const KEY_FINGERPRINT = '_fingerprint';

	const AUTO_SEND_LEVELS = [\Raven_Client::WARN, \Raven_Client::ERROR];
	const LEVEL_MAP = [
		// автоматически не пошлётся, но можно вручную
		'info' => \Raven_Client::INFO,
		'debug' => \Raven_Client::DEBUG,
		// шлётся, но без оповещения
		'notice' => \Raven_Client::WARN,
		'warning' => \Raven_Client::WARN,
		// шлётся с оповещением
		'error' => \Raven_Client::ERROR,
		'critical' => \Raven_Client::ERROR,
		'alert' => \Raven_Client::ERROR,
		'emergency' => \Raven_Client::ERROR,
	];

	/**
	 * то, что после log::write :
	 * sentryLog::log
	 * sentryLog::_log
	 * sentryLog::_sendToSentry
	 */
	const DELETE_TRACE_LEVEL_DEFAULT = 3;
	/**
	 * baseHandler::handleError
	 * AppHandler::_logError
	 * BaseHandler::_logError
	 */
	const DELETE_TRACE_LEVEL_HANDLER = 3;

	/**
	 * клиент сентри
	 *
	 * @var \Raven_Client
	 */
	private static $_client = null;

	/**
	 * доп.инфа
	 *
	 * @var array
	 */
	private static $_addInfo = [];

	/**
	 * Сколько уровней неинформативного трейса отрезать
	 *
	 * @var int
	 */
	private static $_addDeleteTraceLevel = 0;

	/**
	 * Ошибка пришла с обычного хендлера или с шатдауна
	 *
	 * @var bool
	 */
	private static $_isShutdown = false;

	/**
	 * Увеличить число отрезаемых уровней трейса для следующей ошибки
	 *
	 * @param int $add
	 */
	public static function addDeleteTraceLevel($add) {
		self::$_addDeleteTraceLevel += $add;
	}

	/**
	 * Добавить доп. инфу в любой момент
	 *
	 * @param array $info
	 */
	public static function addInfo($info) {
		self::$_addInfo += $info;
	}

	/**
	 * Сказать, что сейчас придёт ошибка из шатдауна
	 */
	public static function setShutdown() {
		self::$_isShutdown = true;
	}

	/**
	 * Получить клиент сентри
	 * Обычно для внутренних нужд, но оставил public на случай, если захочется выполнить что-то изощрённое
	 *
	 * @return \Raven_Client
	 */
	public static function getSentry() {
		if (empty(self::$_client)) {
			// если dsn нет, то просто ничего не будет отсылаться
			$options = (Env::getSentryOptions() ?: []);
			self::$_client = new \Raven_Client(Env::getSentryDsn(), $options);
		}
		return self::$_client;
	}


	/**
	 * @inheritdoc
	 */
	public function log($level, $message, array $context = []) {
		self::_log($level, $message, null, $context);
	}

	/**
	 * логирование в сентри
	 *
	 * @param string $level
	 * @param string $message
	 * @param \Exception|null $exception
	 * @param array $context
	 */
	private static function _log($level, $message, $exception, array $context = []) {
		$sentryLevel = array_key_exists($level, self::LEVEL_MAP) ? self::LEVEL_MAP[$level] : \Raven_Client::ERROR;
		if (
			empty($context[self::KEY_IS_HANDLED])
			&& (
				in_array($sentryLevel, self::AUTO_SEND_LEVELS)
				|| !empty($context[self::KEY_SENTRY_SEND])
			)
		) {
			self::_sendToSentry($sentryLevel, $message, $exception, $context);
		}
		self::$_addDeleteTraceLevel = 0;
	}

	/**
	 * Залогировать ексепшн
	 *
	 * @param \Exception $exception
	 * @param array $context
	 * @param bool $alert
	 */
	public static function logException(\Exception $exception, array $context = [], $alert = true) {
		Env::checkTestException($exception);
		if (empty($context[self::KEY_NO_FILE_LOG])) {
			Log::error($exception->getMessage(), [self::KEY_IS_HANDLED => true] + $context);
		}
		$level = ($alert ? 'error' : 'warning');
		self::_log($level, null, $exception, $context);
	}

	/**
	 * Добавить контекста для отправки в сентри
	 *
	 * @param array|null $context
	 * @SuppressWarnings(PHPMD.FunctionRule)
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	protected static function _addSentryContext($context = null) {
		if (array_key_exists(self::KEY_ADD_INFO, $context)) {
			self::addInfo($context[self::KEY_ADD_INFO]);
		}
		$vars = empty($context[self::KEY_VARS]) ? null : $context[self::KEY_VARS];

		$client = self::getSentry();
		$client->extra_context([
			'_extra_as_string' => self::_exportVar(self::$_addInfo),
			'_defined_vars' => self::_exportVar($vars),
		]);
		if (Env::isCli()) {
			global $argv;
			$client->extra_context(['_argv' => self::_exportVar($argv)]);
		} else {
			$client->extra_context([
				'_post' => self::_exportVar($_POST),
				'_get' => self::_exportVar($_GET),
				'_referrer' => self::_exportVar(empty($_SERVER['HTTP_REFERER']) ? null : $_SERVER['HTTP_REFERER']),
			]);
		}

		if (array_key_exists(self::KEY_TAGS, $context)) {
			$client->tags_context($context[self::KEY_TAGS]);
		}
	}

	/**
	 * экспортировать переменную для передачи в сентри
	 *
	 * @param mixed $var
	 * @return string
	 */
	private static function _exportVar($var) {
		return empty($var) ? 'empty' : Debugger::exportVar($var, self::INFO_MAX_NEST_LEVEL);
	}

	/**
	 * Логировать в сентри
	 *
	 * @param string $level
	 * @param string $message
	 * @param \Exception|null $exception
	 * @param array $context
	 */
	protected static function _sendToSentry($level, $message, $exception, $context) {
		self::_addSentryContext($context);

		$client = self::getSentry();
		$data = ['level' => $level];
		if (!empty($context[self::KEY_FINGERPRINT])) {
			$data['fingerprint'] = $context[self::KEY_FINGERPRINT];
		}

		if (!empty($exception)) {
			// кейк дублирует некоторые ошибки как обычную ошибку и как exeption
			// в большинстве случаев обычная ошибка информативнее
			// но если это настоящий fatal error, вызовется шатдаун, и у обычной ошибки не будет трейса
			// а у ексепшна - будет
			if (self::$_isShutdown || !($exception instanceof FatalErrorException)) {
				$client->captureException($exception, $data);
			}
		} else {
			if (!self::$_isShutdown) {
				$trace = self::_sliceTrace(debug_backtrace(0));
				$client->captureMessage($message, [], $data, $trace);
			}
		}
	}

	/**
	 * чтобы трейс начинался с вызова завписи в лог, не с внутренностей
	 *
	 * @param array $trace
	 * @return array
	 */
	private static function _sliceTrace($trace) {
		// 0, 1, 2 - стереть
		// 3 - Log::write, нужно стереть, если он был вызван из другого метода (например Log::error) или из LogTrait::log, иначе оставить
		// т.е. нужно проверить 4й уровень
		$toSlice = self::DELETE_TRACE_LEVEL_DEFAULT;
		$logWriteCall = $trace[$toSlice];
		$aboveLogWrite = $trace[$toSlice + 1];
		list(, $logTrait) = namespaceSplit(LogTrait::class);
		if (
			(!empty($aboveLogWrite['class']) && ($aboveLogWrite['class'] === Log::class))
			|| (!empty($logWriteCall['file']) && Strings::endsWith($logWriteCall['file'], $logTrait . '.php'))
		) {
			$toSlice++;
		}
		$toSlice += max((int)self::$_addDeleteTraceLevel, 0);

		// trigger_error добавляет в трейс ненужную анонимную функцию
		if (!empty($trace[$toSlice + 1]['function']) && ($trace[$toSlice + 1]['function'] === 'trigger_error')) {
			$toSlice++;
		}
		return array_slice($trace, $toSlice);
	}
}