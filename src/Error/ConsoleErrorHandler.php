<?php
namespace ArtSkills\Error;

use Cake\Error\FatalErrorException;

class ConsoleErrorHandler extends \Cake\Console\ConsoleErrorHandler
{
	use ErrorHandlerTrait;

	/**
	 * @inheritdoc
	 * Убрал отображение fatal error, т.к. это и так выводится на экран
	 */
	public function handleException(\Exception $exception) {
		if (!($exception instanceof FatalErrorException)) {
			$this->_displayException($exception);
		}
		$this->_logException($exception);
		$code = $exception->getCode();
		$code = ($code && is_int($code)) ? $code : 1;
		$this->_stop($code);
	}


}