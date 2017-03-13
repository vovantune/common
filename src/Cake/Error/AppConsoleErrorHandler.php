<?php
namespace ArtSkills\Cake\Error;

use Cake\Core\Configure;
use \Exception;
use \ErrorException;
use Cake\Error\FatalErrorException;

class ConsoleErrorHandler extends \Cake\Console\ConsoleErrorHandler
{
	use SentryHandlerTrait;

	/**
	 * @inheritdoc
	 */
	public function register() {
		$this->_initHandlers();
		$this->_initSentry();
	}

	/**
	 * копия родителя, т.к. там явно в консоли логирование Fatal error отключили
	 */
	private function _initHandlers() {
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
			$this->handleFatalError(
				$error['type'],
				$error['message'],
				$error['file'],
				$error['line']
			);
		});
	}

	/**
	 * @inheritdoc
	 * Убрал отображение fatal error, т.к. это и так выводится на экран
	 */
	public function handleException(Exception $exception) {
		if (!($exception instanceof FatalErrorException)) {
			$this->_displayException($exception);
		}

		$this->_logException($exception);
		$code = $exception->getCode();
		$code = ($code && is_int($code)) ? $code : 1;
		$this->_stop($code);
	}

	/**
	 * @inheritdoc
	 */
	protected function _logError($level, $data) {
		$this->_logSentryException(new ErrorException($data['description'], 0, $data['code'], $data['file'], $data['line']));
		return parent::_logError($level, $data);
	}

	/**
	 * @inheritdoc
	 */
	protected function _logException(Exception $exception) {
		if (!($exception instanceof FatalErrorException)) { // убираем двойное логирование - в том варианте более подробен стэк трейс
			$this->_logSentryException($exception);
			return parent::_logException($exception);
		} else {
			return true;
		}
	}
}