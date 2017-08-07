<?php

namespace ArtSkills\Controller;

use ArtSkills\Error\InternalException;
use ArtSkills\Error\UserException;
use ArtSkills\Lib\Env;
use ArtSkills\Lib\ValueObject;
use Cake\Http\Response;
use Cake\Log\Log;
use Cake\Routing\Router;

class Controller extends \Cake\Controller\Controller
{

	const JSON_STATUS_OK = 'ok';
	const JSON_STATUS_ERROR = 'error';

	const EXTENSION_HTML = 'html';
	const EXTENSION_JSON = 'json';
	const EXTENSION_DEFAULT = self::EXTENSION_HTML;

	/**
	 * Задать редирект в случае ошибки
	 *
	 * @var null|string|array|Response
	 */
	private $_errorRedirect = null;

	/**
	 * Список экшнов, которые всегда должны возвращать джсон.
	 * Автоматически вызывает для них _setIsJsonAction() в инициализации.
	 *
	 * @var string[]
	 */
	protected $_jsonResponseActions = [];

	/** @inheritdoc */
	public function invokeAction() {
		try {
			return parent::invokeAction();
		} catch (UserException $exception) {
			$exception->log();
			if ($this->_isJsonAction()) {
				if (!empty($this->_errorRedirect)) {
					Log::error('Используется редирект в JSON ответе');
				}
				return $this->_sendJsonException($exception);
			}
			$this->Flash->error($exception->getUserMessage());
			$redirect = $this->_errorRedirect;
			if (is_string($redirect) || is_array($redirect)) {
				$redirect = $this->redirect($redirect);
			}
			return $redirect;
		}
	}

	/** @inheritdoc */
	public function isAction($action) {
		$isAction = parent::isAction($action);
		if ($isAction) {
			$methodName = (new \ReflectionMethod($this, $action))->getName();
			if ($methodName !== $action) {
				// разный регистр букв
				$this->request = $this->request->withParam('action', $methodName);
				Router::pushRequest(Router::popRequest()->withParam('action', $methodName));
			}
		}
		return $isAction;
	}

	/** @inheritdoc */
	public function initialize() {
		parent::initialize();
		$currentAction = $this->request->getParam('action');
		foreach ($this->_jsonResponseActions as $action) {
			if ($action === $currentAction) {
				$this->_setIsJsonAction();
				break;
			}
		}
	}

	/**
	 * Задать редирект при обработке ошибок
	 *
	 * @param string|array|Response $redirect
	 */
	protected function _setErrorRedirect($redirect) {
		if (empty($redirect)) {
			$this->_throwInternalError('Пустой параметр $redirect');
		}
		$this->_errorRedirect = $redirect;
	}

	/**
	 * Задать, что при обработке ошибок редиректа нет
	 */
	protected function _setErrorNoRedirect() {
		$this->_errorRedirect = null;
	}

	/**
	 * Бросить обычную пользовательскую ошибку
	 *
	 * @param string $message
	 * @param bool|null|string|array|Response $redirect
	 * @param bool $condition
	 * @throws UserException
	 */
	private function _throwUserErrorAnyResponse($message, $redirect, $condition) {
		if ($condition) {
			if ($redirect !== false) {
				$this->_errorRedirect = $redirect;
			}
			throw new UserException($message);
		}
	}

	/**
	 * При выполнении условия бросить обычную пользовательскую ошибку, используя дефолтное поведение
	 *
	 * @param string $message
	 * @param bool $condition
	 * @throws UserException
	 */
	protected function _throwUserError($message, $condition = true) {
		$this->_throwUserErrorAnyResponse($message, false, $condition);
	}

	/**
	 * При выполнении условия бросить обычную пользовательскую ошибку и сделать редирект
	 *
	 * @param string $message
	 * @param string|array|Response $redirect
	 * @param bool $condition
	 * @throws UserException
	 */
	protected function _throwUserErrorRedirect($message, $redirect, $condition = true) {
		if (empty($redirect)) {
			$this->_throwInternalError('Пустой параметр $redirect');
		}
		$this->_throwUserErrorAnyResponse($message, $redirect, $condition);
	}

	/**
	 * При выполнении условия бросить обычную пользовательскую ошибку и не делать редирект
	 *
	 * @param string $message
	 * @param bool $condition
	 * @throws UserException
	 */
	protected function _throwUserErrorNoRedirect($message, $condition = true) {
		$this->_throwUserErrorAnyResponse($message, null, $condition);
	}

	/**
	 * Бросить обычную внутреннюю ошибку
	 *
	 * @param string $message
	 * @param mixed $addInfo доп информация об ошибке для sentry (SentryLog::KEY_ADD_INFO)
	 * @param string|string[]|null $scope scope для логирования ошибки
	 * @throws InternalException
	 */
	protected function _throwInternalError($message, $addInfo = null, $scope = null) {
		throw InternalException::instance($message)->setLogAddInfo($addInfo)->setLogScope($scope);
	}


	/**
	 * Задать, что текущий экшн должен возвращать json
	 */
	protected function _setIsJsonAction() {
		if (!$this->_isJsonAction()) {
			$this->request = $this->request->withParam('_ext', self::EXTENSION_JSON);
			Router::pushRequest($this->request);
		}
	}

	/**
	 * Узнать, должен ли текущий экшн должен возвращать json
	 *
	 * @return bool
	 */
	protected function _isJsonAction() {
		return ($this->request->getParam('_ext') === self::EXTENSION_JSON);
	}

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
	 * @internal
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
	 * @internal
	 */
	protected function _sendJsonException(\Exception $exception, array $jsonData = []) {
		Env::checkTestException($exception);
		if ($exception instanceof UserException) {
			$message = $exception->getUserMessage();
		} else {
			$message = $exception->getMessage();
		}
		return $this->_sendJsonError($message, $jsonData);
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
