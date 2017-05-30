<?php

namespace ArtSkills\TestSuite\HttpClientMock;

use Cake\Http\Client\Request;
use Cake\Http\Client\Response;

class HttpClientMocker
{
	/**
	 * Коллекция мокнутых вызовов
	 *
	 * @var HttpClientMockerEntity[]
	 */
	private static $_mockCallList = [];

	/**
	 * Сниф запросов и ответов
	 *
	 * @var array
	 */
	private static $_sniffList = [];

	/**
	 * Добавляем элемент
	 *
	 * @param array $element {
	 * @var Request $request
	 * @var Response $response
	 * }
	 */
	public static function addSniff($element) {
		self::$_sniffList[] = $element;
	}

	/**
	 * Выгружаем весь список запросов
	 *
	 * @return array
	 */
	public static function getSniffList() {
		return self::$_sniffList;
	}

	/**
	 * Чистим всё
	 *
	 * @param bool $hasFailed завалился ли тест
	 */
	public static function clean($hasFailed = false) {
		self::$_sniffList = [];

		if (!$hasFailed) {
			foreach (self::$_mockCallList as $mock) {
				$mock->callCheck();
			}
		}
		self::$_mockCallList = [];
	}

	/**
	 * Мокаем HTTP запрос
	 *
	 * @param string|array $url Полная строка, либо массив вида ['урл', ['arg1' => 1, ...]]
	 * @param string $method
	 * @return HttpClientMockerEntity
	 * @throws \Exception
	 */
	public static function mock($url, $method) {
		$mockId = self::_buildKey($url, $method);
		if (isset(self::$_mockCallList[$mockId])) {
			throw new \Exception($url . ' is already mocked with such args');
		}

		self::$_mockCallList[$mockId] = new HttpClientMockerEntity($mockId, $url, $method);
		return self::$_mockCallList[$mockId];
	}

	/**
	 * Мок гет запроса
	 * TODO: затестить
	 *
	 * @param string $url
	 * @param array $uriArgs
	 * @return HttpClientMockerEntity
	 */
	public static function mockGet($url, $uriArgs = []) {
		if (count($uriArgs)) {
			$mockedUrl = $url . (strstr($url, '?') ? '&' : '?') . http_build_query($uriArgs);
		} else {
			$mockedUrl = $url;
		}

		return self::mock($mockedUrl, Request::METHOD_GET);
	}

	/**
	 * Мок пост запроса
	 * TODO: затестить
	 *
	 * @param string $url
	 * @param array|string $expectedPostArgs
	 * @return HttpClientMockerEntity
	 */
	public static function mockPost($url, $expectedPostArgs = []) {
		$mock = self::mock($url, Request::METHOD_POST);
		if (count($expectedPostArgs)) {
			$mock->expectBody($expectedPostArgs);
		}
		return $mock;
	}

	/**
	 * Проверяем на мок и возвращаем результат
	 *
	 * @param Request $request
	 * @return null|string
	 */
	public static function getMockedData(Request $request) {
		foreach (self::$_mockCallList as $mock) {
			$url = (string)$request->getUri();
			$method = $request->getMethod();

			if ($mock->check($url, $method)) {
				return $mock->doAction($request);
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
	private static function _buildKey($url, $method) {
		return $url . '#' . $method;
	}
}
