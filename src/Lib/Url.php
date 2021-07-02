<?php
declare(strict_types=1);

namespace ArtSkills\Lib;

use ArtSkills\Traits\Library;

class Url
{
    use Library;

    public const HTTP = 'http://';
    public const HTTPS = 'https://';


    /**
     * Текущий домен
     *
     * @return string
     */
    public static function domain(): string
    {
        return Env::getServerName();
    }

    /**
     * Текущий протокол
     *
     * @return string
     */
    public static function protocol(): string
    {
        return Env::getServerProtocol();
    }

    /**
     * Текущий домен и протокол
     *
     * @return string
     */
    public static function domainAndProtocol(): string
    {
        return self::_build(self::domain(), self::protocol());
    }

    /**
     * Соединить путь через /
     *
     * @param string[] $parts
     * @return string
     */
    public static function path(array $parts): string
    {
        return trim(implode('/', $parts));
    }

    /**
     * Создать урл с текущим доменом, указанным протоколом, путём и параметрами
     *
     * @param string $domain
     * @param string $protocol
     * @param string[]|string $parts
     * @param array<string, mixed>|string $query
     * @param string $hash
     * @return string
     */
    private static function _build(string $domain, string $protocol = '', $parts = [], $query = [], string $hash = ''): string
    {
        $url = '';
        if (!empty($parts)) {
            $url = self::path((array)$parts);
            if (!empty($url) && ($url[0] !== '/')) {
                $url = '/' . $url;
            }
        }
        if (!empty($domain) && Strings::endsWith($domain, '/')) {
            $domain = Strings::replacePostfix($domain, '/');
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
        if (empty($hash)) {
            $hash = '';
        } elseif ($hash[0] !== '#') {
            $hash = '#' . $hash;
        }
        return $protocol . $domain . $url . $queryString . $hash;
    }

    /**
     * Создать урл с текущим доменом, указанным путём и параметрами
     *
     * @param string[]|string $parts
     * @param array<string, mixed>|string $query
     * @param string $hash
     * @return string
     */
    public static function withDomain($parts = [], $query = [], string $hash = ''): string
    {
        return self::_build(self::domain(), '', $parts, $query, $hash);
    }

    /**
     * http://Текущий домен, или адрес с ним
     *
     * @param string[]|string $parts
     * @param array<string, mixed>|string $query
     * @param string $hash
     * @return string
     */
    public static function withDomainHttp($parts = [], $query = [], string $hash = ''): string
    {
        return self::_build(self::domain(), 'http', $parts, $query, $hash);
    }


    /**
     * Текуший протокол://домен, или адрес с ним
     *
     * @param string[]|string $parts
     * @param array<string, mixed>|string $query
     * @param string $hash
     * @return string
     */
    public static function withDomainAndProtocol($parts = [], $query = [], string $hash = ''): string
    {
        return self::_build(self::domain(), self::protocol(), $parts, $query, $hash);
    }

    /**
     * Адрес с любым доменом
     *
     * @param string $domain
     * @param string[]|string $parts
     * @param array<string, mixed>|string $query
     * @param string $hash
     * @return string
     */
    public static function withCustomDomain(string $domain, $parts = [], $query = [], string $hash = '')
    {
        return self::_build($domain, '', $parts, $query, $hash);
    }

    /**
     * Адрес без домена
     *
     * @param string[]|string $parts
     * @param array<string, mixed>|string $query
     * @param string $hash
     * @return string
     */
    public static function withoutDomain($parts = [], $query = [], string $hash = ''): string
    {
        return self::_build('', '', $parts, $query, $hash);
    }


    /**
     * Распарсивает строку с параметрами запроса в массив
     *
     * @param string $queryString
     * @return array<string, mixed>
     */
    public static function parseQuery(string $queryString): array
    {
        parse_str($queryString, $result);
        if (empty($result)) {
            return [];
        }
        return $result;
    }

    /**
     * http_build_query
     *
     * @param array<string, mixed> $parts
     * @return string
     */
    public static function buildQuery(array $parts)
    {
        return http_build_query($parts);
    }

    /**
     * Проверить, что строка начинается с http:// или https://
     *
     * @param string $url
     * @return bool
     */
    public static function isHttpUrl(string $url): bool
    {
        return Strings::startsWith(trim($url), [self::HTTP, self::HTTPS]);
    }
}
