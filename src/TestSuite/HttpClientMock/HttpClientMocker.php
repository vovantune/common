<?php
declare(strict_types=1);

namespace ArtSkills\TestSuite\HttpClientMock;

use Cake\Http\Client\Request;
use PHPUnit\Framework\ExpectationFailedException;

class HttpClientMocker
{
    /**
     * Коллекция мокнутых вызовов
     *
     * @var HttpClientMockerEntity[]
     */
    private static array $_mockCallList = [];

    /**
     * Сниф запросов и ответов
     *
     * @var array<int, array{request: Request, response: Response}>
     */
    private static array $_sniffList = [];

    /**
     * Добавляем элемент
     *
     * @param array{request: Request, response: Response} $element
     * @return void
     */
    public static function addSniff($element)
    {
        self::$_sniffList[] = $element;
    }

    /**
     * Выгружаем весь список запросов
     *
     * @return array<int, array{request: Request, response: Response}>
     */
    public static function getSniffList(): array
    {
        return self::$_sniffList;
    }

    /**
     * Чистим всё
     *
     * @param bool $hasFailed завалился ли тест
     * @return void
     */
    public static function clean(bool $hasFailed = false)
    {
        self::$_sniffList = [];

        try {
            if (!$hasFailed) {
                foreach (self::$_mockCallList as $mock) {
                    $mock->callCheck();
                }
            }
        } finally {
            self::$_mockCallList = [];
        }
    }

    /**
     * Мокаем HTTP запрос
     *
     * @param string|array $url Полная строка, либо массив вида ['урл', ['arg1' => 1, ...]]
     * @param string $method
     * @return HttpClientMockerEntity
     * @throws ExpectationFailedException
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public static function mock($url, string $method): HttpClientMockerEntity
    {
        $mockId = self::_buildKey($url, $method);
        if (isset(self::$_mockCallList[$mockId])) {
            throw new ExpectationFailedException($method . ' ' . $url . ' is already mocked');
        }

        self::$_mockCallList[$mockId] = new HttpClientMockerEntity($url, $method);
        return self::$_mockCallList[$mockId];
    }

    /**
     * Мок гет запроса
     *
     * @param string $url
     * @param array<string, int|string|bool|null> $uriArgs
     * @return HttpClientMockerEntity
     */
    public static function mockGet(string $url, array $uriArgs = []): HttpClientMockerEntity
    {
        if (count($uriArgs)) {
            $mockedUrl = $url . ((strpos($url, '?') === false) ? '?' : '&') . http_build_query($uriArgs);
        } else {
            $mockedUrl = $url;
        }

        return self::mock($mockedUrl, Request::METHOD_GET);
    }

    /**
     * Мок пост запроса
     *
     * @param string $url
     * @param null|array<string, int|string|bool|null>|string $expectedPostArgs
     * @return HttpClientMockerEntity
     */
    public static function mockPost(string $url, $expectedPostArgs = null): HttpClientMockerEntity
    {
        $mock = self::mock($url, Request::METHOD_POST);
        if ($expectedPostArgs !== null) {
            $mock->expectBody($expectedPostArgs);
        }
        return $mock;
    }

    /**
     * Проверяем на мок и возвращаем результат
     *
     * @param Request $request
     * @return null|array{response: string, status: int}
     */
    public static function getMockedData(Request $request): ?array
    {
        foreach (self::$_mockCallList as $mock) {
            $url = (string)$request->getUri();
            $method = $request->getMethod();

            if ($mock->check($url, $method)) {
                $response = $mock->doAction($request);
                // doAction вызывается до getReturnStatusCode, потому что в нём статус может измениться
                $statusCode = $mock->getReturnStatusCode();
                return ['response' => $response, 'status' => $statusCode];
            }
        }

        return null;
    }

    /**
     * Формируем уникальный ключ
     *
     * @param string $url
     * @param string $method
     * @return string
     */
    private static function _buildKey(string $url, string $method): string
    {
        return $url . '#' . $method;
    }
}
