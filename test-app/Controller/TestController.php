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

}