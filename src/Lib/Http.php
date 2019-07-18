<?php

namespace ArtSkills\Lib;

use ArtSkills\Http\Client;
use ArtSkills\Traits\Library;
use Cake\Http\Client\Response;

class Http
{
	use Library;

	/**
	 * Экземпляр http клиента
	 *
	 * @var null
	 */
	private static $_httpClient = null;

	/**
	 * Работает по типу file_get_contents но только для url
	 *
	 * @param string $url
	 * @return string|null
	 */
	public static function getContent($url)
	{
		$request = static::_makeRequest();
		$result = static::_getRequest($url, $request);

		if (!empty($result)) {
			return $result->getStringBody();
		} else {
			return null;
		}
	}

	/**
	 * Возвращает JSON ответ на get запрос
	 *
	 * @param string $url
	 * @param array $data
	 * @param array $options
	 * @return array|null
	 */
	public static function getJson($url, $data = [], $options = [])
	{
		$request = static::_makeRequest();
		return static::_getResponseJson(static::_getRequest($url, $request, $data, $options));
	}

	/**
	 * Возвращает Json ответ на Post запрос
	 *
	 * @param string $url
	 * @param array $data
	 * @param array $options
	 * @return array|null
	 */
	public static function postJson($url, $data, $options = [])
	{
		$request = self::_makeRequest();
		return static::_getResponseJson($request->post($url, $data, $options));
	}

	/**
	 * Возвращает Json ответ на Put запрос
	 *
	 * @param string $url
	 * @param array $data
	 * @param array $options
	 * @return array|null
	 */
	public static function putJson($url, $data, $options = [])
	{
		$request = self::_makeRequest();
		return static::_getResponseJson($request->put($url, $data, $options));
	}

	/**
	 * Возвращает XML ответ
	 *
	 * @param string $url
	 * @return \SimpleXMLElement|null
	 */
	public static function getXml($url)
	{
		$request = static::_makeRequest();
		$result = static::_getRequest($url, $request);
		if (!empty($result)) {
			return $result->getXml();
		} else {
			return null;
		}
	}

	/**
	 * Возвращает XML ответ при POST запросе
	 *
	 * @param string $url
	 * @param array $data
	 * @param array $options
	 * @return \SimpleXMLElement|null
	 */
	public static function postXml($url, $data, $options = [])
	{
		$request = static::_makeRequest();
		$result = $request->post($url, $data, $options);
		if (!empty($result)) {
			return $result->getXml();
		} else {
			return null;
		}
	}

	/**
	 * Скачивает файл по указанной ссылке в targetFile или во временную директорию
	 *
	 * @param string $url
	 * @param string $targetFile
	 * @param int $timeout
	 * @return string
	 */
	public static function downloadFile($url, $targetFile = '', $timeout = 30)
	{

		if (empty($targetFile)) {
			$targetFile = TMP . uniqid() . '.tmp';
		}

		@$fileHandle = fopen($targetFile, 'w+');
		if (empty($fileHandle)) {
			return '';
		}

		$curlHandle = curl_init($url);

		curl_setopt($curlHandle, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($curlHandle, CURLOPT_FILE, $fileHandle);
		curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, true);
		curl_exec($curlHandle);
		$err = curl_error($curlHandle);
		curl_close($curlHandle);

		fclose($fileHandle);

		if (!empty($err)) {
			unlink($targetFile);
			return '';
		}


		return $targetFile;
	}

	/**
	 * Возвращает JSON от запроса с проверкой на его выполнение
	 *
	 * @param Response|null $response
	 * @return array|null
	 */
	private static function _getResponseJson($response)
	{
		if (!empty($response)) {
			return $response->getJson();
		} else {
			return null;
		}
	}

	/**
	 * Выполняет get запрос
	 *
	 * @param string $url
	 * @param Client $request
	 * @param array $data
	 * @param array $options
	 * @return Response
	 */
	private static function _getRequest($url, $request, $data = [], $options = [])
	{
		return $request->get($url, $data, $options);
	}

	/**
	 * Создает экземпляр клиента если нужно
	 *
	 * @return Client
	 */
	private static function _makeRequest()
	{
		if (static::$_httpClient == null) {
			static::$_httpClient = new Client();
		}
		return static::$_httpClient;
	}
}