<?php
namespace ArtSkills\TestSuite\HttpClientMock;


use Cake\Http\Client\Adapter\Stream;
use Cake\Http\Client\Request;
use Cake\Http\Client\Response;

/**
 * Прослайка на отправку HTTP запросов
 *
 * @package App\Test\Suite
 */
class HttpClientAdapter extends Stream
{
	/**
	 * Полная инфа по текущему взаимодействию (запрос и ответ)
	 *
	 * @var array|null
	 */
	private $_currentRequestData = null;

	private static $_debugRequests = true;

	/**
	 * Все запросы проверяются на подмену, а также логипуются
	 *
	 * @param Request $request
	 * @return array
	 */
	protected function _send(Request $request) {
		$this->_currentRequestData = [
			'request' => $request,
			'response' => '',
		];

		$mockedData = HttpClientMocker::getMockedData($request);
		if ($mockedData !== null) {
			return $this->createResponses(['HTTP/1.1 200 OK', 'Server: nginx/1.2.1'], $mockedData);
		} else {
			/**
			 * @var Response[] $result
			 */
			$result = parent::_send($request);

			if (self::$_debugRequests) {
				print "==============================================================\n";
				print "Do " . $request->getMethod() . ' request to ' . $request->getUri() . ', Body: ' . $request->getBody() . "\n";
				print "Response: \n" . $result[0]->body() . "\n";
				print "==============================================================\n";

			}

			return $result;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function createResponses($headers, $content) {
		$result = parent::createResponses($headers, $content);
		/**
		 * @var Response $lastResponse
		 */
		$lastResponse = $result[count($result) - 1];
		$this->_currentRequestData['response'] = $lastResponse;

		HttpClientMocker::addSniff($this->_currentRequestData);
		$this->_currentRequestData = null;

		return $result;
	}

	/**
	 * Включаем вывод запросов в консоль
	 */
	public static function enableDebug() {
		self::$_debugRequests = true;
	}

	/**
	 * Выключаем вывод запросов в консоль
	 */
	public static function disableDebug() {
		self::$_debugRequests = false;
	}
}
