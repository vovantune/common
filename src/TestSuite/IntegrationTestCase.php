<?php
namespace ArtSkills\TestSuite;

use ArtSkills\Controller\Controller;
use ArtSkills\Error\InternalException;
use ArtSkills\Lib\Arrays;
use ArtSkills\Lib\Env;
use ArtSkills\TestSuite\Mock\MethodMocker;
use ArtSkills\TestSuite\Mock\MethodMockerEntity;
use Cake\Controller\Component\FlashComponent;


abstract class IntegrationTestCase extends \Cake\TestSuite\IntegrationTestCase
{

	use TestCaseTrait;

	/**
	 * Снифф флеша
	 *
	 * @var null|MethodMockerEntity
	 */
	protected $_flashSniff = null;

	/**
	 * С чем вызывался флеш
	 *
	 * @var array
	 */
	protected $_flashResult = [];

	/**
	 * Загружаем фикстуру из хранилища
	 *
	 * @param string $fixtureName
	 * @return mixed
	 * @throws InternalException
	 */
	protected function _getJsonFixture($fixtureName) {
		if (Env::hasFixtureFolder()) {
			$fixtureFolder = Env::getFixtureFolder();
		} else {
			throw new InternalException('Не указана папка с фикстурами!');
		}
		$fileName = $fixtureFolder . 'Json' . DS . $fixtureName . '.json';
		if (!is_file($fileName)) {
			throw new InternalException('Fixture file "' . $fileName . '" does not exist!');
		}

		return Arrays::decode(file_get_contents($fileName));
	}

	/**
	 * Проверка, что JSON-массив содержит переданный подмассив
	 *
	 * @param string|array|\ArrayAccess $subset
	 * @param string $json
	 * @param bool $strict Check for object identity
	 * @param string $message
	 * @return void
	 */
	public function assertJsonSubset($subset, $json, $strict = false, $message = '') {
		if (is_string($subset)) {
			$subset = json_decode($subset, true);
		}
		$array = json_decode($json, true);
		self::assertArraySubset($subset, $array, $strict, $message);
	}

	/**
	 * Проверка, что ответ - JSON и его decode
	 *
	 * @param string $url
	 * @param null|int $responseCode
	 * @return array
	 */
	public function getJsonResponse($url = '', $responseCode = null) {
		if (!empty($url)) {
			$this->get($url);
		}

		if ($responseCode === null) {
			$this->assertResponseOk();
		} else {
			$this->assertResponseCode($responseCode);
		}

		$rawBody = (string)$this->_response->getBody();

		self::assertJson($rawBody, 'Получен ответ не в формате JSON');
		return json_decode($rawBody, true);
	}

	/**
	 * Отправляем POST запрос и получаем JSON результат
	 *
	 * @param string $url
	 * @param string|array $data
	 * @return array
	 */
	public function postJsonResponse($url, $data) {
		$this->post($url, $data);
		return $this->getJsonResponse();
	}

	/**
	 * Проверка JSON ответа
	 *
	 * @param array $expected
	 * @param string $message
	 * @param null|int $responseCode
	 * @param float $delta
	 * @param int $maxDepth
	 */
	public function assertJsonResponseEquals($expected, $message = '', $responseCode = null, $delta = 0.0, $maxDepth = 10) {
		self::assertEquals($expected, $this->getJsonResponse('', $responseCode), $message, $delta, $maxDepth);
	}

	/**
	 * Проверка вхождения в JSON ответ
	 *
	 * @param array $subset
	 * @param string $message
	 * @param null|int $responseCode
	 * @param float $delta
	 * @param int $maxDepth
	 */
	public function assertJsonResponseSubset(
		$subset, $message = '', $responseCode = null, $delta = 0.0, $maxDepth = 10
	) {
		$this->assertArraySubsetEquals($subset, $this->getJsonResponse('', $responseCode), $message, $delta, $maxDepth);
	}

	/**
	 * Проверка JSON ответа с ошибкой
	 *
	 * @param string $expectedMessage
	 * @param string $message
	 * @param array $expectedData
	 * @param null|int $responseCode
	 * @param float $delta
	 * @param int $maxDepth
	 */
	public function assertJsonErrorEquals(
		$expectedMessage, $message = '', $expectedData = [], $responseCode = null, $delta = 0.0, $maxDepth = 10
	) {
		$expectedResponse = ['status' => Controller::JSON_STATUS_ERROR, 'message' => $expectedMessage] + $expectedData;
		$this->assertJsonResponseEquals($expectedResponse, $message, $responseCode, $delta, $maxDepth);
	}

	/**
	 * Проверка JSON ответа с ошибкой
	 *
	 * @param string $expectedMessage
	 * @param string $message
	 * @param array $expectedData
	 * @param float $delta
	 * @param int $maxDepth
	 */
	public function assertJsonInternalErrorEquals(
		$expectedMessage, $message = '', $expectedData = [], $delta = 0.0, $maxDepth = 10
	) {
		$expectedResponse = ['status' => Controller::JSON_STATUS_ERROR, 'message' => $expectedMessage] + $expectedData;
		$this->assertJsonResponseSubset($expectedResponse, $message, 500, $delta, $maxDepth);
	}

	/**
	 * Проверка успешного JSON ответа
	 *
	 * @param array $expectedData
	 * @param string $message
	 * @param null|int $responseCode
	 * @param float $delta
	 * @param int $maxDepth
	 */
	public function assertJsonOKEquals($expectedData = [], $message = '', $responseCode = null, $delta = 0.0, $maxDepth = 10) {
		$expectedResponse = ['status' => Controller::JSON_STATUS_OK] + $expectedData;
		$this->assertJsonResponseEquals($expectedResponse, $message, $responseCode, $delta, $maxDepth);
	}

	/** @inheritdoc */
	protected function _sendRequest($url, $method, $data = []) {
		$this->_flashResult = [];
		parent::_sendRequest($url, $method, $data);
	}

	/**
	 * Снифф флеша
	 *
	 * @param int $expectCall
	 */
	protected function _initFlashSniff($expectCall = MethodMockerEntity::EXPECT_CALL_ONCE) {
		$this->_flashSniff = MethodMocker::sniff(FlashComponent::class, '__call')
			->expectCall($expectCall)
			->willReturnAction(function ($args) {
				$this->_flashResult[] = $args;
			});
	}

	/**
	 * Проверка, что можно применять ассерты флеша
	 */
	protected function _checkFlashInited() {
		if (empty($this->_flashSniff)) {
			self::fail('Flash sniff is not inited');
		}
	}

	/**
	 * Проверка на все вызовы флеша в реквесте
	 *
	 * @param array $expectedFlash массив массивов [method, [message]]
	 * @param string $message
	 */
	private function _assertFlashEquals($expectedFlash, $message = '') {
		$this->_checkFlashInited();
		self::assertEquals($expectedFlash, $this->_flashResult, $message);
	}

	/**
	 * Проверка, что был всего один флеш и он был успешный
	 *
	 * @param string $expectedMessage
	 * @param string $assertFailMessage
	 */
	public function assertFlashSuccess($expectedMessage, $assertFailMessage = '') {
		$this->assertFlashMany([$expectedMessage => 'success'], $assertFailMessage);
	}

	/**
	 * Проверка, что был всего один флеш и он был ошибочный
	 *
	 * @param string $expectedMessage
	 * @param string $assertFailMessage
	 */
	public function assertFlashError($expectedMessage, $assertFailMessage = '') {
		$this->assertFlashMany([$expectedMessage =>'error'], $assertFailMessage);
	}

	/**
	 * Проверка нескольких сообщений флеша
	 *
	 * @param array $expectedMessages сообщение => тип
	 * @param string $assertFailMessage
	 */
	public function assertFlashMany(array $expectedMessages, $assertFailMessage = '') {
		$expectedFlash = [];
		foreach ($expectedMessages as $expectedMessage => $messageType) {
			$expectedFlash[] = [$messageType, [$expectedMessage]];
		}
		$this->_assertFlashEquals($expectedFlash, $assertFailMessage);
	}

	/**
	 * Проверка, что было много ошибок
	 *
	 * @param array $expectedErrors
	 * @param string $assertFailMessage
	 */
	public function assertFlashManyErrors(array $expectedErrors, $assertFailMessage = '') {
		$this->assertFlashMany(array_fill_keys($expectedErrors, 'error'), $assertFailMessage);
	}

	/**
	 * Проверка, что хотя бы один из вызовов флеша был таким
	 *
	 * @param array $expectedFlash массив [method, [message]]
	 * @param string $message
	 */
	private function _assertInFlash($expectedFlash, $message = '') {
		$this->_checkFlashInited();
		self::assertContains($expectedFlash, $this->_flashResult, $message);
	}

	/**
	 * Проверка, что во флеше был успех
	 *
	 * @param string $expectedMessage
	 * @param string $assertFailMessage
	 */
	public function assertFlashHasSuccess($expectedMessage, $assertFailMessage = '') {
		$this->_assertInFlash(['success', [$expectedMessage]], $assertFailMessage);
	}

	/**
	 * Проверка, что во флеше была ошибка
	 *
	 * @param string $expectedMessage
	 * @param string $assertFailMessage
	 */
	public function assertFlashHasError($expectedMessage, $assertFailMessage = '') {
		$this->_assertInFlash(['error', [$expectedMessage]], $assertFailMessage);
	}

	/**
	 * Проверка, что флеш не вызывался
	 *
	 * @param string $message
	 */
	public function assertNoFlash($message = '') {
		$this->_checkFlashInited();
		self::assertEquals([], $this->_flashResult, $message);
	}

	/**
	 * Задаёт хедер User-Agent, чтоб срабатывали проверки на линукс
	 */
	protected function _setLinuxHeaders() {
		$this->_setHeader('User-Agent', 'linux');
	}

	/**
	 * Задаёт хедер реферера
	 *
	 * @param string $refererUrl
	 * @param string $webroot
	 */
	protected function _setReferer($refererUrl, $webroot = '/') {
		$this->_setHeader('referer', $refererUrl);
		if (!array_key_exists('webroot', $this->_request)) {
			$this->_request['webroot'] = $webroot;
		}
	}

	/**
	 * Задаёт хедер
	 *
	 * @param string $name
	 * @param string $value
	 */
	protected function _setHeader($name, $value) {
		if (empty($this->_request['headers'])) {
			$this->_request['headers'] = [];
		}
		$this->_request['headers'][$name] = $value;
	}

}
