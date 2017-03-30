<?php
namespace ArtSkills\Lib;

use ArtSkills\Traits\Library;

class Url
{
	use Library;

	const HTTP = 'http://';


	/**
	 * Текущий домен
	 *
	 * @return string
	 */
	public static function domain() {
		return Env::getServerName();
	}

	/**
	 * Текущий протокол
	 *
	 * @return string
	 */
	public static function protocol() {
		return Env::getServerProtocol();
	}

	/**
	 * Текущий домен и протокол
	 *
	 * @return string
	 */
	public static function domainAndProtocol() {
		return self::_build(self::domain(), self::protocol());
	}

	/**
	 * Соединить путь через /
	 *
	 * @param string[] $parts
	 * @return string
	 */
	public static function path($parts) {
		return trim(implode('/', $parts));
	}

	/**
	 * Создать урл с текущим доменом, указанным протоколом, путём и параметрами
	 *
	 * @param string $domain
	 * @param string $protocol
	 * @param string[]|string $parts
	 * @param array|string $query
	 * @return string
	 */
	private static function _build($domain, $protocol, $parts = [], $query = []) {
		$url = '';
		if (!empty($parts)) {
			$url = self::path((array)$parts);
			if (!empty($url) && ($url[0] !== '/')) {
				$url = '/' . $url;
			}
		}
		$queryString = '';
		if (!empty($query)) {
			if (is_array($query)) {
				$queryString = self::buildQuery($query);
			} else {
				$queryString = $query;
			}
			if (!empty($queryString) && ($queryString[0] !== '?')) {
				$queryString = '?' . $queryString;
			}
		}
		if (empty($protocol)) {
			$protocol = '';
		} else {
			$protocol = $protocol . '://';
		}
		return $protocol . $domain . $url . $queryString;
	}

	/**
	 * Создать урл с текущим доменом, указанным путём и параметрами
	 *
	 * @param string[]|string $parts
	 * @param array|string $query
	 * @return string
	 */
	public static function withDomain($parts = [], $query = []) {
		return self::_build(self::domain(), false, $parts, $query);
	}

	/**
	 * http://Текущий домен, или адрес с ним
	 *
	 * @param string[]|string $parts
	 * @param array|string $query
	 * @return string
	 */
	public static function withDomainHttp($parts = [], $query = []) {
		return self::_build(self::domain(), 'http', $parts, $query);
	}


	/**
	 * Текуший протокол://домен, или адрес с ним
	 *
	 * @param string[]|string $parts
	 * @param array|string $query
	 * @return string
	 */
	public static function withDomainAndProtocol($parts = [], $query = []) {
		return self::_build(self::domain(), self::protocol(), $parts, $query);
	}

	/**
	 * Адрес с любым доменом
	 *
	 * @param string $domain
	 * @param array $parts
	 * @param array $query
	 * @return string
	 */
	public static function withCustomDomain($domain, $parts = [], $query = []) {
		return self::_build($domain, false, $parts, $query);
	}

	/**
	 * Адрес без домена
	 *
	 * @param array $parts
	 * @param array $query
	 * @return string
	 */
	public static function withoutDomain($parts = [], $query = []) {
		return self::_build('', false, $parts, $query);
	}


	/**
	 * Распарсивает строку с параметрами запроса в массив
	 *
	 * @param string $queryString
	 * @return array
	 */
	public static function parseQuery($queryString) {
		parse_str($queryString, $result);
		if (empty($result)) {
			return [];
		}
		return $result;
	}

	/**
	 * http_build_query
	 *
	 * @param array $parts
	 * @return string
	 */
	public static function buildQuery(array $parts) {
		return http_build_query($parts);
	}

	/**
	 * Проверить, что строка начинается с http://
	 *
	 * @param string $url
	 * @return bool
	 */
	public static function isHttpUrl($url) {
		return Strings::startsWith(trim($url), self::HTTP);
	}


}