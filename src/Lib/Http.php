<?php
namespace ArtSkills\Lib;

use ArtSkills\Cake\Http\Client;
use ArtSkills\Traits\Library;

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
	 * @return string mixed
	 */
	public static function getContent($url) {
		$request = static::_makeRequest();
		return static::_getRequest($url, $request)->body();
	}

	/**
	 * Возвращает JSON ответ на get запрос
	 *
	 * @param string $url
	 * @return array mixed
	 */
	public static function getJson($url) {
		$request = static::_makeRequest();
		return static::_getRequest($url, $request)->json;
	}

	/**
	 * Возвращает Json ответ на Post запрос
	 * @param string $url
	 * @param array $data
	 * @return array mixed
	 */
	public static function postJson($url, $data) {
		$request = self::_makeRequest();
		return static::_postRequest($url, $request, $data)->json;
	}


	/**
	 * Возвращает XML ответ
	 *
	 * @param string $url
	 * @return \SimpleXMLElement|null
	 */
	public static function getXml($url) {
		$request = static::_makeRequest();
		return static::_getRequest($url, $request)->xml;
	}

	/**
	 * Выполняет get запрос
	 *
	 * @param string $url
	 * @param Client $request
	 * @return mixed
	 */
	private static function _getRequest($url, $request) {
		return $request->get($url);
	}

	/**
	 * Post запрос
	 *
	 * @param string $url
	 * @param Client $request
	 * @param array $data
	 * @return mixed
	 */
	private static function _postRequest($url, $request, $data) {
		return $request->post($url, $data);
	}

	/**
	 * Создает экземпляр клиента если нужно
	 *
	 * @return Client
	 */
	private static function _makeRequest() {
		if (static::$_httpClient == null) {
			static::$_httpClient = new Client();
		}
		return static::$_httpClient;
	}

	/**
	 * Скачивает файл по указанной ссылке в targetFile или во временную директорию
	 *
	 * @param string $url
	 * @param string $targetFile
	 * @param int $timeout
	 * @return string
	 */
	public static function downloadFile($url, $targetFile = '', $timeout = 30) {

		if (empty($targetFile)) {
			$targetFile = TMP . uniqid() . '.tmp';
		}

		@$fileHandle = fopen($targetFile, 'w+');
		if (empty($fileHandle)) {
			return '';
		}

		$curlHandle = curl_init($url);

		curl_setopt($curlHandle, CURLOPT_TIMEOUT, $timeout);
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
}
