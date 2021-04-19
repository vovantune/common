<?php

namespace ArtSkills\TestSuite\HttpClientMock;

use ArtSkills\Lib\Arrays;
use Cake\Http\Client\Request;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;

class HttpClientMockerEntity
{

    /**
     * запрос должен быть вызван хотя бы раз
     */
    const EXPECT_CALL_ONCE = -1;

    /**
     * Файл, в котором мокнули
     *
     * @var string
     */
    private $_callerFile = '';

    /**
     * Строка вызова к HttpClientMocker::mock
     *
     * @var int
     */
    private $_callerLine = 0;

    /**
     * Мокнутый урл
     *
     * @var string
     */
    private $_url = '';

    /**
     * Метод запроса
     *
     * @var string
     */
    private $_method = Request::METHOD_GET;

    /**
     * POST тело запроса
     *
     * @var array
     */
    private $_body = null;

    /**
     * Возвращаемый результат
     *
     * @var null|mixed
     */
    private $_returnValue = null;

    /**
     * Возвращаемое событие
     *
     * @var null|callable
     */
    private $_returnAction = null;

    /**
     * Статус возвращаемого ответа
     *
     * @var int
     */
    private $_returnStatusCode = 200;

    /**
     * Сколько раз ожидается вызов функции
     *
     * @var int
     */
    private $_expectedCallCount = self::EXPECT_CALL_ONCE;

    /**
     * Кол-во вызовов данного мока
     *
     * @var int
     */
    private $_callCounter = 0;

    /**
     * Был ли вызов данного мока
     *
     * @var bool
     */
    private $_isCalled = false;

    /**
     * Мок отработал и все вернул в первоначальный вид
     *
     * @var bool
     */
    private $_mockChecked = false;

    /**
     * HttpClientMockerEntity constructor.
     *
     * @param string $url
     * @param string $method
     */
    public function __construct($url, $method = Request::METHOD_GET)
    {
        $this->_url = $url;
        $this->_method = $method;

        $dropTraceFiles = [
            __FILE__,
            (new \ReflectionClass(HttpClientMocker::class))->getFileName(),
        ];
        $mockedIn = null;
        $trace = debug_backtrace();
        foreach ($trace as $callData) {
            if (!empty($callData['file'])
                && !empty($callData['line'])
                && !in_array($callData['file'], $dropTraceFiles, true)
            ) {
                $mockedIn = $callData;
                break;
            }
        }
        $this->_callerFile = $mockedIn['file'];
        $this->_callerLine = $mockedIn['line'];
    }

    /**
     * Проверяем, относится ли текущий мок к запросу с данными параметрами
     *
     * @param string $url
     * @param string $method
     * @return bool
     */
    public function check($url, $method)
    {
        if ($this->_url !== $url) {
            return false;
        }

        if ($this->_method !== $method) {
            return false;
        }
        return true;
    }

    /**
     * Омечаем, что функция должна вызываться разово
     *
     * @return $this
     */
    public function singleCall()
    {
        return $this->expectCall(1);
    }

    /**
     * Омечаем, что функция должна вызываться как минимум 1 раз
     *
     * @return $this
     */
    public function anyCall()
    {
        return $this->expectCall(self::EXPECT_CALL_ONCE);
    }

    /**
     * Ожидаем, что таких вызовов не будет
     *
     * @return $this
     */
    public function noCalls()
    {
        return $this->expectCall(0);
    }

    /**
     * Ограничение на количество вызовов данного мока
     *
     * @param int $times
     * @return $this
     */
    public function expectCall($times = 1)
    {
        $this->_expectedCallCount = $times;
        return $this;
    }

    /**
     * Заполняем тело запроса для POST и прочих методов
     *
     * @param array|string $body
     * @return $this
     * @throws ExpectationFailedException
     */
    public function expectBody($body)
    {
        if ($this->_method === Request::METHOD_GET) {
            $this->_fail('Body for GET method is not required!');
        }

        $this->_body = $body;
        return $this;
    }

    /**
     * Ожидается пустое тело запроса
     *
     * @return HttpClientMockerEntity
     */
    public function expectEmptyBody()
    {
        return $this->expectBody('');
    }

    /**
     * Что вернет запрос
     *
     * @param string $value
     * @return $this
     */
    public function willReturnString($value)
    {
        $this->_returnAction = null;
        $this->_returnValue = $this->_processResponse($value);
        return $this;
    }

    /**
     * Вернет закодированный json
     *
     * @param array $value
     * @return $this
     */
    public function willReturnJson($value)
    {
        return $this->willReturnString(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    /**
     * Возвращаем содержимое файла
     *
     * @param string $filePath
     * @return HttpClientMockerEntity
     */
    public function willReturnFile($filePath)
    {
        if (!is_file($filePath)) {
            $this->_fail($filePath . ' is not a file');
        }
        return $this->willReturnString(file_get_contents($filePath));
    }

    /**
     * Вернется результат функции $action(array Аргументы, [mixed Результат от оригинального метода])
     * Пример:
     * ->willReturnAction(function($request){
     *    return 'result body';
     * });
     *
     * @param callable $action
     * @return $this
     */
    public function willReturnAction($action)
    {
        $this->_returnAction = $action;
        $this->_returnValue = null;
        return $this;
    }

    /**
     * Задать статус возвращаемого ответа
     *
     * @param int $statusCode
     * @return $this
     */
    public function willReturnStatus($statusCode)
    {
        if (!is_int($statusCode) || ($statusCode < 100) || ($statusCode > 599)) {
            $this->_fail('Status code should be integer between 100 and 599');
        }
        $this->_returnStatusCode = $statusCode;
        return $this;
    }

    /**
     * Мок событие
     *
     * @param Request $request
     * @return string
     * @throws AssertionFailedError
     * @throws ExpectationFailedException
     */
    public function doAction($request)
    {
        if (($this->_expectedCallCount > self::EXPECT_CALL_ONCE) && ($this->_callCounter >= $this->_expectedCallCount)) {
            $this->_fail('expected ' . $this->_expectedCallCount . ' calls, but more appeared');
        }

        $this->_callCounter++;
        $this->_isCalled = true;

        $actualBody = (string)$request->getBody();
        if ($this->_body === null) {
            if (($request->getMethod() === Request::METHOD_POST) && empty($actualBody)) {
                // Post с пустым body - скорее всего ошибка
                // Если это не ошибка, то надо явно вызвать ->expectEmptyBody()
                $this->_fail('Post request with empty body');
            }
        } else {
            if (is_array($this->_body)) {
                $contentTypes = $request->getHeader('Content-Type');
                $contentType = Arrays::get($contentTypes, 0);
                switch ($contentType) {
                    case 'application/x-www-form-urlencoded':
                        $parsedBody = [];
                        parse_str($actualBody, $parsedBody);
                        $actualBody = $parsedBody;
                        break;
                    case 'application/json':
                        $actualBody = json_decode($actualBody, true);
                        break;
                }
            }
            Assert::assertEquals($this->_body, $actualBody, 'Expected POST body data is not equal to real data');
        }

        if ($this->_returnValue !== null) {
            $response = $this->_returnValue;
        } elseif ($this->_returnAction !== null) {
            $action = $this->_returnAction;
            // передаю $this, чтобы внутри можно было задать код статуса
            $response = $action($request, $this);
        } else {
            $this->_fail('Return mock action is not defined');
            return null;
        }

        return $this->_processResponse($response);
    }

    /**
     * Получить код статуса ответа
     *
     * @return int
     */
    public function getReturnStatusCode()
    {
        return $this->_returnStatusCode;
    }

    /**
     * Кол-во вызовов данного мока
     *
     * @return int
     */
    public function getCallCount()
    {
        return $this->_callCounter;
    }

    /**
     * Финальная проверка на вызовы
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function callCheck()
    {
        if ($this->_mockChecked) {
            return;
        }

        $goodCallCount = (
            (($this->_expectedCallCount == self::EXPECT_CALL_ONCE) && $this->_isCalled)
            || ($this->_expectedCallCount == $this->getCallCount())
        );
        $this->_mockChecked = true;

        if (!$goodCallCount) {
            $this->_fail(
                $this->_isCalled
                    ? 'is called ' . $this->getCallCount() . ' times, expected ' . $this->_expectedCallCount
                    : 'is not called!'
            );
        }
    }

    /**
     * Формируем сообщение об ошибке
     *
     * @param string $msg
     * @return string
     */
    private function _getErrorMessage($msg)
    {
        return $this->_url . '(mocked in ' . $this->_callerFile . ' line ' . $this->_callerLine . ') - ' . $msg;
    }

    /**
     * Не прошла проверка, заваливаем тест
     *
     * @param string $message
     * @throws ExpectationFailedException
     */
    private function _fail($message)
    {
        throw new ExpectationFailedException($this->_getErrorMessage($message));
    }

    /**
     * Обработать возвращаемое значение.
     * null превращается в пустую строку,
     * при любых других нестроковых значениях кидается ошибка
     *
     * @param mixed $response
     * @return string
     * @throws ExpectationFailedException
     */
    private function _processResponse($response)
    {
        if ($response === null) {
            $response = '';
        } elseif (!is_string($response)) {
            $this->_fail('Invalid response: ' . print_r($response, true));
        }
        return $response;
    }
}
