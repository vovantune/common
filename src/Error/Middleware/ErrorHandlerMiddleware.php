<?php

namespace ArtSkills\Error\Middleware;

use ArtSkills\Log\Engine\SentryLog;

class ErrorHandlerMiddleware extends \Cake\Error\Middleware\ErrorHandlerMiddleware
{

	/**
	 * @inheritdoc
	 * Копия родительского метода.
	 * Чтобы использовать SentryLog::logException().
	 * По-умолчанию был плохой трейс и нельзя делать warn.
	 * И исключения phpunit теперь прокидываются дальше.
	 */
	protected function logException($request, $exception) {
		if (!$this->getConfig('log')) {
			return;
		}

		$skipLog = $this->getConfig('skipLog');
		if ($skipLog) {
			foreach ((array)$skipLog as $class) {
				if ($exception instanceof $class) {
					return;
				}
			}
		}

		SentryLog::logException($exception, [
			SentryLog::KEY_ADD_INFO => [
				'cakeMessage' => $this->getMessage($request, $exception),
			],
		]);
	}

	/**
	 * @inheritdoc
	 * Копия родительского метода.
	 * Поднял logException наверх, чтобы он вызывался до render.
	 * Таким образом при ошибке внутри render исходная ошибка тоже будет залогирована.
	 * И исключения phpunit теперь прокидываются дальше.
	 */
	public function handleException($exception, $request, $response) {
		$renderer = $this->getRenderer($exception);
		try {
			$this->logException($request, $exception);
			$res = $renderer->render();

			return $res;
		} catch (\Exception $e) {
			$this->logException($request, $e);

			$body = $response->getBody();
			$body->write('An Internal Server Error Occurred');
			$response = $response->withStatus(500)
				->withBody($body);
		}

		return $response;
	}

}