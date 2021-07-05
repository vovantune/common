<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\TestSuite\HttpClientMock;

use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\HttpClientMock\HttpClientMockerEntity;
use Cake\Http\Client\Request;
use \PHPUnit\Framework\ExpectationFailedException;

class HttpClientMockerEntityTest extends AppTestCase
{
    const DEFAULT_TEST_URL = 'http://www.artskills.ru';
    const DEFAULT_POST_DATA = ['foo' => 'barr', 'bar' => 'babar'];

    /**
     * Разовый вызов
     */
    public function testOnceAndReturnString(): void
    {
        $testUrl = self::DEFAULT_TEST_URL;
        $testData = self::DEFAULT_POST_DATA;
        $correctTestData = ['bar' => 'babar', 'foo' => 'barr'];
        $testMethod = Request::METHOD_POST;
        $returnVal = 'test';

        $mock = new HttpClientMockerEntity($testUrl, $testMethod);
        $mock->singleCall()
            ->expectBody($testData)
            ->willReturnString($returnVal);

        $request = new Request($testUrl, $testMethod);
        $request->body($correctTestData);

        self::assertTrue($mock->check($testUrl, $testMethod));
        self::assertEquals($returnVal, $mock->doAction($request));
        self::assertEquals(1, $mock->getCallCount());
        self::assertEquals(200, $mock->getReturnStatusCode());
    }

    /**
     * Несколько раз вызвали с кэлбаком
     */
    public function testAnyAndReturnAction(): void
    {
        $testUrl = self::DEFAULT_TEST_URL;
        $testMethod = Request::METHOD_GET;
        $returnVal = 'test';

        $mock = new HttpClientMockerEntity($testUrl, $testMethod);
        $mock->willReturnAction(function () use ($returnVal) {
            return $returnVal;
        });

        $request = new Request($testUrl, $testMethod);

        self::assertTrue($mock->check($testUrl, $testMethod));
        self::assertEquals($returnVal, $mock->doAction($request));
        self::assertEquals($returnVal, $mock->doAction($request));
        self::assertEquals(2, $mock->getCallCount());
        self::assertEquals(200, $mock->getReturnStatusCode());
    }

    /**
     * Защита от вызова несколько раз
     */
    public function testSingleCallCheck(): void
    {
        $this->expectExceptionMessage("expected 1 calls, but more appeared");
        $this->expectException(ExpectationFailedException::class);
        $mock = $this->_makeGetMock()
            ->singleCall()
            ->willReturnString('test');
        $request = $this->_makeGetRequest();

        $mock->doAction($request);
        $mock->doAction($request);
    }

    /**
     * Ни разу не вызвали
     */
    public function testNoCallCheck(): void
    {
        $this->expectExceptionMessage("is not called");
        $this->expectException(ExpectationFailedException::class);
        $mock = $this->_makeGetMock();
        $mock->callCheck();
    }

    /** Ни разу не вызвали, но так и должно быть */
    public function testExpectedNoCallCheck(): void
    {
        $mock = $this->_makeGetMock()->noCalls();
        $mock->callCheck();
        self::assertTrue(true, 'Не кинулся ексепшн');
    }


    /**
     * Проверка check метода
     */
    public function testCheck(): void
    {
        $testUrl = self::DEFAULT_TEST_URL;
        $testMethod = Request::METHOD_GET;

        $mock = $this->_makeMock($testMethod, $testUrl);
        self::assertFalse($mock->check('blabla', $testMethod));
        self::assertFalse($mock->check($testUrl, Request::METHOD_DELETE));
        self::assertTrue($mock->check($testUrl, $testMethod));
    }

    /**
     * Не указали возвращаемый результат
     */
    public function testEmptyResultCheck(): void
    {
        $this->expectExceptionMessage("Return mock action is not defined");
        $this->expectException(ExpectationFailedException::class);
        $this->_makeGetMock()->doAction($this->_makeGetRequest());
    }

    /**
     * Указали POST данные для GET запроса
     */
    public function testBodySetForGetMethod(): void
    {
        $this->expectExceptionMessage("Body for GET method is not required");
        $this->expectException(ExpectationFailedException::class);
        $this->_makeGetMock()->expectBody(self::DEFAULT_POST_DATA);
    }

    /**
     * Задали плохой код ответа
     */
    public function testSetStatusCodeBad(): void
    {
        $this->expectExceptionMessage("Status code should be integer between 100 and 599");
        $this->expectException(ExpectationFailedException::class);
        $this->_makeGetMock()->willReturnStatus(999);
    }

    /**
     * Задали слишком маленький код ответа
     */
    public function testSetStatusCodeSmall(): void
    {
        $this->expectExceptionMessage("Status code should be integer between 100 and 599");
        $this->expectException(ExpectationFailedException::class);
        $this->_makeGetMock()->willReturnStatus(99);
    }

    /**
     * Задали слишком большой код ответа
     */
    public function testSetStatusCodeBig(): void
    {
        $this->expectExceptionMessage("Status code should be integer between 100 and 599");
        $this->expectException(ExpectationFailedException::class);
        $this->_makeGetMock()->willReturnStatus(600);
    }

    /**
     * Ответ - не строка
     */
    public function testReturnActionBad(): void
    {
        $this->expectExceptionMessage("Invalid response: Array");
        $this->expectException(ExpectationFailedException::class);
        $this->_makeGetMock()
            ->willReturnAction(function () {
                return ['asd' => 'qwe'];
            })
            ->doAction($this->_makeGetRequest());
    }

    /**
     * Не существующий файл
     */
    public function testReturnFileNotExists(): void
    {
        $this->expectExceptionMessage("is not a file");
        $this->expectException(ExpectationFailedException::class);
        $this->_makeGetMock()->willReturnFile(__DIR__ . DS . 'non_existent_file.txt');
    }

    /**
     * Указали не файл
     */
    public function testReturnFileIsNotFile(): void
    {
        $this->expectExceptionMessage("is not a file");
        $this->expectException(ExpectationFailedException::class);
        $this->_makeGetMock()->willReturnFile(__DIR__);
    }

    /** содержимое ответа берётся из файла */
    public function testReturnFile(): void
    {
        $result = $this->_makeGetMock()
            ->willReturnFile(__DIR__ . DS . 'file_response.txt')
            ->doAction($this->_makeGetRequest());
        self::assertEquals('response in a file', $result);
    }

    /**
     * отправился запрос не с тем body,  которым ожидалось
     */
    public function testUnexpectedBody(): void
    {
        $this->expectExceptionMessage("Expected POST body data is not equal to real data");
        $this->expectException(ExpectationFailedException::class);
        $this->_makePostMock(['asd' => 'qwe'])
            ->willReturnString('')
            ->doAction($this->_makePostRequest(['asd' => 'zxc']));
    }

    /**
     * Отправился запрос с пустым body.
     * Будет ошибка, если явно не указать, что ожидался пустой body
     */
    public function testEmptyPost(): void
    {
        $this->expectExceptionMessage("Post request with empty body");
        $this->expectException(ExpectationFailedException::class);
        $this->_makePostMock(null)
            ->willReturnString('')
            ->doAction($this->_makePostRequest(''));
    }

    /** сравнение содержимого post body, сравнение проходит */
    public function testExpectBodyGood(): void
    {
        $mock = $this->_makePostMock()->willReturnString('');

        $request = $this->_makePostRequest(['asd' => 'qwe']);
        $mock->expectBody(['asd' => 'qwe'])->doAction($request);
        $mock->expectBody('asd=qwe')->doAction($request);

        $request = $this->_makePostRequest('{"rty":"fgh"}')->withHeader('content-type', 'application/json');
        $mock->expectBody(['rty' => 'fgh'])->doAction($request);
        $mock->expectBody('{"rty":"fgh"}')->doAction($request);

        // не проверять body
        $mock->expectBody(null)->doAction($request);
        // явно задано, что ожидается пустота
        $mock->expectEmptyBody()->doAction($this->_makePostRequest(''));

        self::assertTrue(true, 'Не выкинулся ексепшн');
    }


    /**
     * Получить объект Request с методом GET
     *
     * @param string $url
     * @return Request
     */
    private function _makeGetRequest(string $url = self::DEFAULT_TEST_URL): Request
    {
        return $this->_makeRequest(Request::METHOD_GET, $url);
    }

    /**
     * Получить объект Request с методом POST
     *
     * @param array|string $data
     * @param string $url
     * @return Request
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    private function _makePostRequest($data = self::DEFAULT_POST_DATA, string $url = self::DEFAULT_TEST_URL): Request
    {
        return $this->_makeRequest(Request::METHOD_POST, $url, $data);
    }

    /**
     * Получить объект Request
     *
     * @param string $method
     * @param string $url
     * @param array|string $data
     * @return Request
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    private function _makeRequest(string $method, string $url = self::DEFAULT_TEST_URL, $data = self::DEFAULT_POST_DATA): Request
    {
        $request = new Request($url, $method);
        if ($method === Request::METHOD_POST) {
            $request->body($data);
        }
        return $request;
    }

    /**
     * Получить объект мока запроса GET
     *
     * @param string $url
     * @return HttpClientMockerEntity
     */
    private function _makeGetMock(string $url = self::DEFAULT_TEST_URL): HttpClientMockerEntity
    {
        return $this->_makeMock(Request::METHOD_GET, $url);
    }

    /**
     * Получить объект мока запроса POST
     *
     * @param array|string|null $data
     * @param string $url
     * @return HttpClientMockerEntity
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    private function _makePostMock($data = self::DEFAULT_POST_DATA, $url = self::DEFAULT_TEST_URL): HttpClientMockerEntity
    {
        return $this->_makeMock(Request::METHOD_POST, $url, $data);
    }

    /**
     * Получить объект мока запроса
     *
     * @param string $method
     * @param string $url
     * @param array|string $data
     * @return HttpClientMockerEntity
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    private function _makeMock(string $method, string $url = self::DEFAULT_TEST_URL, $data = self::DEFAULT_POST_DATA): HttpClientMockerEntity
    {
        $mock = new HttpClientMockerEntity($url, $method);
        if ($method === Request::METHOD_POST) {
            $mock->expectBody($data);
        }
        return $mock;
    }
}
