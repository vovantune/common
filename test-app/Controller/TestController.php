<?php

namespace TestApp\Controller;

use ArtSkills\Controller\Controller;

class TestController extends Controller
{
	/**
	 * Успешный JSON ответ
	 *
	 * @return null
	 */
	public function getJsonOk() {
		return $this->_sendJsonOk(['testProperty' => 123]);
	}

	/**
	 * Сообщение об ошибке
	 *
	 * @return NULL
	 */
	public function getJsonError() {
		return $this->_sendJsonError('Тестовая ошибка', ['errorProperty' => 123]);
	}

	/**
	 * JSON ответ из ValueObject
	 */
	public function getValueObjectJson() {
		return $this->_sendJsonOk((new TestValueObject()));
	}

	/**
	 * Null в ответ
	 *
	 * @return null
	 */
	public function getEmptyJson() {
		return $this->_sendJsonResponse([]);
	}

	/**
	 * Ошибка из ексепшна
	 *
	 * @return null
	 */
	public function getJsonException() {
		return $this->_sendJsonException(new \Exception('test exception'), ['someData' => 'test']);
	}

	/**
	 * Ошибки phpunit прокидываются дальше
	 *
	 * @return null
	 */
	public function getJsonExceptionUnit() {
		return $this->_sendJsonException(new \PHPUnit_Framework_AssertionFailedError('test unit exception'), ['someData' => 'test']);
	}



}