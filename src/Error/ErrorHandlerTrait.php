<?php
namespace ArtSkills\Error;

use ArtSkills\Log\Engine\SentryLog;
use Cake\Core\Configure;
use Cake\Error\FatalErrorException;
use Cake\Error\PHP7ErrorException;

trait ErrorHandlerTrait
{
	/**
	 * @inheritdoc
	 * Копипаста из родителя
	 * убрали условие, по которому ошибки не логировались в шатдауне
	 * дополнительные действия для сентри
	 */
	public function register() {
		$level = -1;
		if (isset($this->_options['errorLevel'])) {
			$level = $this->_options['errorLevel'];
		}
		error_reporting($level);
		set_error_handler([$this, 'handleError'], $level);
		set_exception_handler([$this, 'wrapAndHandleException']);
		register_shutdown_function(function () {
			$megabytes = Configure::read('Error.extraFatalErrorMemory');
			if ($megabytes === null) {
				$megabytes = 4;
			}
			if ($megabytes > 0) {
				$this->increaseMemoryLimit($megabytes * 1024);
			}
			$error = error_get_last();
			if (!is_array($error)) {
				return;
			}
			$fatals = [
				E_USER_ERROR,
				E_ERROR,
				E_PARSE,
			];
			if (!in_array($error['type'], $fatals, true)) {
				return;
			}
			$this->_logShutdown($error);
		});
	}

	/**
	 * Залогировать шатдаун
	 *
	 * @param array $error
	 */
	private function _logShutdown($error) {
		SentryLog::setShutdown();
		$this->handleFatalError(
			$error['type'],
			$error['message'],
			$error['file'],
			$error['line']
		);
	}

	/**
	 * @inheritdoc
	 * копия родителя
	 * дополнительные действия для сентри
	 */
	public function handleFatalError($code, $description, $file, $line) {
		SentryLog::addDeleteTraceLevel(1);
		$data = [
			'code' => $code,
			'description' => $description,
			'file' => $file,
			'line' => $line,
			'error' => 'Fatal Error',
		];
		// в кейке получается двойное логирование, можно было бы убрать
		// но кейк неправильно себя ведёт без handleException
		// и в шатдауне он информативнее
		$this->_logError(LOG_ERR, $data);
		$this->handleException(new FatalErrorException($description, 500, $file, $line));

		return true;
	}

	/**
	 * @inheritdoc
	 * все notice и warning логировать как ошибки
	 */
	protected function _logError($level, $data) {
		SentryLog::addDeleteTraceLevel(SentryLog::DELETE_TRACE_LEVEL_HANDLER);
		return parent::_logError(LOG_ERR, $data);
	}

	/**
	 * @inheritdoc
	 * скопировано из родителя
	 * добавлена обработка в сентри
	 */
	protected function _logException(\Exception $exception) {
		$config = $this->_options;
		$unwrapped = $exception instanceof PHP7ErrorException ?
			$exception->getError() :
			$exception;

		if (empty($config['log'])) {
			return false;
		}

		if (!empty($config['skipLog'])) {
			foreach ((array)$config['skipLog'] as $class) {
				if ($unwrapped instanceof $class) {
					return false;
				}
			}
		}
		$params = [];
		if ($exception instanceof FatalErrorException) {
			// в этом случае в файл уже написали
			$params[SentryLog::KEY_NO_FILE_LOG] = true;
		}
		SentryLog::logException($exception, $params);
		return true;
	}
}
