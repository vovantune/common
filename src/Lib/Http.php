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
     * @var null|Client
     */
    private static ?Client $_httpClient = null;

    /**
     * Работает по типу file_get_contents но только для url
     *
     * @param string $url
     * @return string|null
     */
    public static function getContent(string $url): ?string
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
     * @param array|string $data
     * @param array $options
     * @return array|null
     */
    public static function getJson(string $url, $data = [], array $options = []): ?array
    {
        $request = static::_makeRequest();
        return static::_getResponseJson(static::_getRequest($url, $request, $data, $options));
    }

    /**
     * Возвращает Json ответ на Post запрос
     *
     * @param string $url
     * @param array|string $data
     * @param array $options
     * @return array|null
     */
    public static function postJson(string $url, $data, array $options = [])
    {
        $request = self::_makeRequest();
        return static::_getResponseJson($request->post($url, $data, $options));
    }

    /**
     * Возвращает Json ответ на Put запрос
     *
     * @param string $url
     * @param array|string $data
     * @param array $options
     * @return array|null
     */
    public static function putJson(string $url, $data, array $options = []): ?array
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
    public static function getXml(string $url): ?\SimpleXMLElement
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
     * @param array|string $data
     * @param array $options
     * @return \SimpleXMLElement|null
     */
    public static function postXml(string $url, $data, array $options = []): ?\SimpleXMLElement
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
    public static function downloadFile(string $url, string $targetFile = '', int $timeout = 30): string
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
    private static function _getResponseJson(?Response $response): ?array
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
     * @param array|string $data
     * @param array $options
     * @return Response|null
     */
    private static function _getRequest(string $url, Client $request, $data = [], array $options = []): ?Response
    {
        return $request->get($url, $data, $options);
    }

    /**
     * Создает экземпляр клиента если нужно
     *
     * @return Client
     */
    private static function _makeRequest(): ?Client
    {
        if (static::$_httpClient == null) {
            static::$_httpClient = new Client();
        }
        return static::$_httpClient;
    }
}
