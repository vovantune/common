<?php
namespace ArtSkills\Cake\Error;

use ArtSkills\Lib\Env;
use \Exception;
use \Raven_Client;

trait SentryHandlerTrait
{
	/**
	 * Raven_Client
	 *
	 * @var Raven_Client
	 */
	private $_client = null;

	/**
	 * Инициализация
	 */
	protected function _initSentry() {
		if (Env::hasSentryDsn()) {
			$this->_client = new Raven_Client(Env::getSentryDsn());
		}
	}

	/**
	 * Логируем Exception
	 *
	 * @param Exception $exception
	 */
	protected function _logSentryException(Exception $exception) {
		if ($this->_client) {
			$this->_client->captureException($exception);
		}
	}
}