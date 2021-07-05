<?php
declare(strict_types=1);

namespace ArtSkills\TestSuite;

use ArtSkills\Controller\Controller;
use ArtSkills\Error\InternalException;
use ArtSkills\Lib\Arrays;
use ArtSkills\Lib\Env;
use ArtSkills\TestSuite\Mock\MethodMocker;
use ArtSkills\TestSuite\Mock\MethodMockerEntity;
use Cake\Controller\Component\FlashComponent;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

abstract class IntegrationTestCase extends TestCase
{

    use TestCaseTrait, IntegrationTestTrait {
        _sendRequest as protected _traitSendRequest;
    }

    /**
     * Снифф флеша
     *
     * @var null|MethodMockerEntity
     */
    protected ?MethodMockerEntity $_flashSniff = null;

    /**
     * С чем вызывался флеш
     *
     * @var array<int, array{0: string, 1: string[]}>
     */
    protected array $_flashResult = [];

    /**
     * Загружаем фикстуру из хранилища
     *
     * @param string $fixtureName
     * @return array|null|string
     * @throws InternalException
     */
    protected function _getJsonFixture(string $fixtureName)
    {
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
     * @SuppressWarnings(PHPMD.MethodArgs)
     * @phpstan-ignore-next-line
     */
    public function assertJsonSubset($subset, string $json, bool $strict = false, string $message = '')
    {
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
     * @SuppressWarnings(PHPMD.MethodArgs)
     * @phpstan-ignore-next-line
     */
    public function getJsonResponse(string $url = '', ?int $responseCode = null): array
    {
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
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public function postJsonResponse(string $url, $data): array
    {
        $this->post($url, $data);
        return $this->getJsonResponse();
    }

    /**
     * Проверка JSON ответа
     *
     * @param array $expected
     * @param string $message
     * @param int|null $responseCode
     * @param float $delta
     * @param int $maxDepth
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public function assertJsonResponseEquals(
        array $expected,
        string $message = '',
        int $responseCode = null,
        float $delta = 0.0,
        int $maxDepth = 10
    ) {
        self::assertEquals($expected, $this->getJsonResponse('', $responseCode), $message, $delta, $maxDepth);
    }

    /**
     * Проверка вхождения в JSON ответ
     *
     * @param array $subset
     * @param string $message
     * @param int|null $responseCode
     * @param float $delta
     * @param int $maxDepth
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public function assertJsonResponseSubset(
        array $subset,
        string $message = '',
        int $responseCode = null,
        float $delta = 0.0,
        int $maxDepth = 10
    ) {
        $this->assertArraySubsetEquals($subset, $this->getJsonResponse('', $responseCode), $message, $delta, $maxDepth);
    }

    /**
     * Проверка JSON ответа с ошибкой
     *
     * @param string $expectedMessage
     * @param string $message
     * @param array $expectedData
     * @param int|null $responseCode
     * @param float $delta
     * @param int $maxDepth
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public function assertJsonErrorEquals(
        string $expectedMessage,
        string $message = '',
        array $expectedData = [],
        int $responseCode = null,
        float $delta = 0.0,
        int $maxDepth = 10
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
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public function assertJsonInternalErrorEquals(
        string $expectedMessage,
        string $message = '',
        array $expectedData = [],
        float $delta = 0.0,
        int $maxDepth = 10
    ) {
        $expectedResponse = ['status' => Controller::JSON_STATUS_ERROR, 'message' => $expectedMessage] + $expectedData;
        $this->assertJsonResponseSubset($expectedResponse, $message, 500, $delta, $maxDepth);
    }

    /**
     * Проверка успешного JSON ответа
     *
     * @param array $expectedData
     * @param string $message
     * @param int|null $responseCode
     * @param float $delta
     * @param int $maxDepth
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public function assertJsonOKEquals(
        array $expectedData = [],
        string $message = '',
        int $responseCode = null,
        float $delta = 0.0,
        int $maxDepth = 10
    ) {
        $expectedResponse = ['status' => Controller::JSON_STATUS_OK] + $expectedData;
        $this->assertJsonResponseEquals($expectedResponse, $message, $responseCode, $delta, $maxDepth);
    }

    /**
     * @inheritdoc
     * @phpstan-ignore-next-line
     */
    protected function _sendRequest($url, $method, $data = [])
    {
        $this->_flashResult = [];
        $this->_traitSendRequest($url, $method, $data); // @phpstan-ignore-line некорректно работает с переопределением метода трейта
    }

    /**
     * Снифф флеша
     *
     * @param int $expectCall
     * @return void
     */
    protected function _initFlashSniff(int $expectCall = MethodMockerEntity::EXPECT_CALL_ONCE)
    {
        $this->_flashSniff = MethodMocker::sniff(FlashComponent::class, '__call')
            ->expectCall($expectCall)
            ->willReturnAction(function ($args) {
                $this->_flashResult[] = $args;
            });
    }

    /**
     * Проверка, что можно применять ассерты флеша
     *
     * @return void
     */
    protected function _checkFlashInited()
    {
        if (empty($this->_flashSniff)) {
            self::fail('Flash sniff is not inited');
        }
    }

    /**
     * Проверка на все вызовы флеша в реквесте
     *
     * @param array<int, array{0: string, 1: string[]}> $expectedFlash массив массивов [method, [message]]
     * @param string $message
     * @return void
     */
    private function _assertFlashEquals(array $expectedFlash, string $message = '')
    {
        $this->_checkFlashInited();
        self::assertEquals($expectedFlash, $this->_flashResult, $message);
    }

    /**
     * Проверка, что был всего один флеш и он был успешный
     *
     * @param string $expectedMessage
     * @param string $assertFailMessage
     * @return void
     */
    public function assertFlashSuccess(string $expectedMessage, string $assertFailMessage = '')
    {
        $this->assertFlashMany([$expectedMessage => 'success'], $assertFailMessage);
    }

    /**
     * Проверка, что был всего один флеш и он был ошибочный
     *
     * @param string $expectedMessage
     * @param string $assertFailMessage
     * @return void
     */
    public function assertFlashError(string $expectedMessage, string $assertFailMessage = '')
    {
        $this->assertFlashMany([$expectedMessage => 'error'], $assertFailMessage);
    }

    /**
     * Проверка нескольких сообщений флеша
     *
     * @param array<string, string> $expectedMessages сообщение => тип
     * @param string $assertFailMessage
     * @return void
     */
    public function assertFlashMany(array $expectedMessages, string $assertFailMessage = '')
    {
        $expectedFlash = [];
        foreach ($expectedMessages as $expectedMessage => $messageType) {
            $expectedFlash[] = [$messageType, [$expectedMessage]];
        }
        $this->_assertFlashEquals($expectedFlash, $assertFailMessage);
    }

    /**
     * Проверка, что было много ошибок
     *
     * @param string[] $expectedErrors
     * @param string $assertFailMessage
     * @return void
     */
    public function assertFlashManyErrors(array $expectedErrors, string $assertFailMessage = '')
    {
        $this->assertFlashMany(array_fill_keys($expectedErrors, 'error'), $assertFailMessage);
    }

    /**
     * Проверка, что хотя бы один из вызовов флеша был таким
     *
     * @param array{0: string, 1: string[]} $expectedFlash массив [method, [message]]
     * @param string $message
     * @return void
     */
    private function _assertInFlash(array $expectedFlash, string $message = '')
    {
        $this->_checkFlashInited();
        self::assertContains($expectedFlash, $this->_flashResult, $message);
    }

    /**
     * Проверка, что во флеше был успех
     *
     * @param string $expectedMessage
     * @param string $assertFailMessage
     * @return void
     */
    public function assertFlashHasSuccess(string $expectedMessage, string $assertFailMessage = '')
    {
        $this->_assertInFlash(['success', [$expectedMessage]], $assertFailMessage);
    }

    /**
     * Проверка, что во флеше была ошибка
     *
     * @param string $expectedMessage
     * @param string $assertFailMessage
     * @return void
     */
    public function assertFlashHasError(string $expectedMessage, string $assertFailMessage = '')
    {
        $this->_assertInFlash(['error', [$expectedMessage]], $assertFailMessage);
    }

    /**
     * Проверка, что флеш не вызывался
     *
     * @param string $message
     * @return void
     */
    public function assertNoFlash(string $message = '')
    {
        $this->_checkFlashInited();
        self::assertEquals([], $this->_flashResult, $message);
    }

    /**
     * Задаёт хедер реферера
     *
     * @param string $refererUrl
     * @param string $webroot
     * @return void
     */
    protected function _setReferer(string $refererUrl, string $webroot = '/')
    {
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
     * @return void
     */
    protected function _setHeader(string $name, string $value)
    {
        if (empty($this->_request['headers'])) {
            $this->_request['headers'] = [];
        }
        $this->_request['headers'][$name] = $value;
    }
}
