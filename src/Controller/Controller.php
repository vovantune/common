<?php

namespace ArtSkills\Controller;

use ArtSkills\Lib\Env;
use ArtSkills\Lib\ValueObject;

class Controller extends \Cake\Controller\Controller
{

	const JSON_STATUS_OK = 'ok';
	const JSON_STATUS_ERROR = 'error';

	/**
	 * Возвращает ответ без ошибки и прерывает выполнение
	 *
	 * @param array|ValueObject $jsonData
	 * @return NULL
	 */

	protected function _sendJsonOk($jsonData = []) {
		if ($jsonData instanceof ValueObject) {
			$jsonData = $jsonData->toArray();
		}

		return $this->_sendJsonResponse(['status' => self::JSON_STATUS_OK] + $jsonData);
	}

	/**
	 * Возвращает ответ с ошибкой, сообщением, и прерывает выполнение
	 *
	 * @param string $message
	 * @param array $jsonData дополнительные параметры если нужны
	 * @return NULL
	 */
	protected function _sendJsonError($message, array $jsonData = []) {
		return $this->_sendJsonResponse(['status' => self::JSON_STATUS_ERROR, 'message' => $message] + $jsonData);
	}

	/**
	 * Вернуть json-ответ с ошибкой, сообщение берётся из $exception->getMessage().
	 * Исключения PHPUnit прокидываются дальше
	 *
	 * @param \Exception $exception
	 * @param array $jsonData
	 * @return NULL
	 */
	protected function _sendJsonException(\Exception $exception, array $jsonData = []) {
		Env::checkTestException($exception);
		return $this->_sendJsonError($exception->getMessage(), $jsonData);
	}

	/**
	 * Отправляем JSON/JSONP ответ клиенту
	 *
	 * @param array $jsonArray
	 * @return null
	 * @internal У нас стандартизированный JSON: _sendJsonOk и _sendJsonError
	 */
	protected function _sendJsonResponse(array $jsonArray) {
		if (empty($jsonArray)) { // Дабы null не слать
			$jsonArray['status'] = self::JSON_STATUS_OK;
		}

		$jsonArray['_serialize'] = array_keys($jsonArray);
		$jsonArray['_jsonOptions'] = JSON_UNESCAPED_UNICODE;

		$this->set($jsonArray);
		$this->viewBuilder()->setClassName('Json');
		$jsonPResponse = !empty($this->request->getQuery('callback'));
		if ($jsonPResponse) {
			$this->response->type('application/x-javascript');
			$this->set('_jsonp', true);
		} else {
			$this->response->type('application/json');
		}
		return null;
	}
}
