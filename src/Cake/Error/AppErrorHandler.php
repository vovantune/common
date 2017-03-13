<?php
namespace ArtSkills\Cake\Error;

use Cake\Error\ErrorHandler;
use \Exception;
use \ErrorException;
use Cake\Error\FatalErrorException;

class AppErrorHandler extends ErrorHandler
{
	use SentryHandlerTrait;

	/**
	 * @inheritdoc
	 */
	public function register() {
		parent::register();
		$this->_initSentry();
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