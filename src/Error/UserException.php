<?php

namespace ArtSkills\Error;

class UserException extends Exception
{

	/**
	 * Сообщение, которое будет выведено юзеру.
	 * По умолчанию это то же самое, что и message, но можно задать что-то другое.
	 * message используется для записи в лог
	 *
	 * @var string
	 */
	protected $_userMessage = '';

	/**
	 * @inheritdoc
	 * по умолчанию выключено
	 */
	protected $_writeToLog = false;


	/** @inheritdoc */
	public function __construct($message = '', $code = 0, $previous = null)
	{
		parent::__construct($message, $code, $previous);
		$this->setUserMessage($this->message);
	}

	/**
	 * Получить сообщение для юзера
	 *
	 * @return string
	 */
	public function getUserMessage()
	{
		return $this->_userMessage;
	}


	/**
	 * Задать специальное сообщение для юзера
	 *
	 * @param string $message
	 * @return $this
	 */
	public function setUserMessage($message)
	{
		$this->_userMessage = (string)$message;
		return $this;
	}
}