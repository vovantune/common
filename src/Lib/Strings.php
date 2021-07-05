<?php
declare(strict_types=1);

namespace ArtSkills\Lib;

use ArtSkills\Traits\Library;

class Strings
{
    use Library;

    /**
     * Проверка, что строка начинается с любого префикса из списка
     *
     * @param string $string
     * @param string|string[] $prefixes
     * @return bool
     */
    public static function startsWith(string $string, $prefixes): bool
    {
        foreach ((array)$prefixes as $prefix) {
            if (stripos($string, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Проверка, что строка заканчивается любым постфиксом из списка
     *
     * @param string $string
     * @param string|string[] $postfixes
     * @return bool
     */
    public static function endsWith(string $string, $postfixes): bool
    {
        $stringLength = strlen($string);
        foreach ((array)$postfixes as $postfix) {
            if (strripos($string, $postfix) === ($stringLength - strlen($postfix))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Заменить или отрезать префикс у строки
     * Без проверок, когда точно знаете, что префикс есть
     * str_replace заменяет все вхождения, так что он не подходит
     *
     * @param string $string
     * @param string $prefix
     * @param string $replacement
     * @return string
     */
    public static function replacePrefix(string $string, string $prefix, string $replacement = ''): string
    {
        return $replacement . (substr($string, strlen($prefix)));
    }

    /**
     * Заменить или отрезать постфикс у строки
     * Без проверок, когда точно знаете, что постфикс есть
     * str_replace заменяет все вхождения, так что он не подходит
     *
     * @param string $string
     * @param string $postfix
     * @param string $replacement
     * @return string
     */
    public static function replacePostfix(string $string, string $postfix, string $replacement = ''): string
    {
        return (substr($string, 0, -strlen($postfix))) . $replacement;
    }

    /**
     * Заменить префикс из списка, если строка начинается с него
     *
     * @param string $string
     * @param string[]|string $prefixes
     * @param string $replacement
     * @param bool $concatOnFail добавить $replacement в случае отсутствия префикса или нет
     * @return string
     */
    public static function replaceIfStartsWith(string $string, $prefixes, string $replacement = '', bool $concatOnFail = false): string
    {
        foreach ((array)$prefixes as $prefix) {
            if (self::startsWith($string, $prefix)) {
                return self::replacePrefix($string, $prefix, $replacement);
            }
        }
        if ($concatOnFail) {
            $string = $replacement . $string;
        }
        return $string;
    }

    /**
     * Заменить постфикс из списка, если строка заканчивается им
     *
     * @param string $string
     * @param string[]|string $postfixes
     * @param string $replacement
     * @param bool $concatOnFail добавить $replacement в случае отсутствия префикса или нет
     * @return string
     */
    public static function replaceIfEndsWith(string $string, $postfixes, string $replacement = '', bool $concatOnFail = false): string
    {
        foreach ((array)$postfixes as $postfix) {
            if (self::endsWith($string, $postfix)) {
                return self::replacePostfix($string, $postfix, $replacement);
            }
        }
        if ($concatOnFail) {
            $string .= $replacement;
        }
        return $string;
    }


    /**
     * сделать array_pop() от результата explode()
     * для array_pop() нужно создавать временную переменную, что раздражает
     *
     * @param string $delimiter
     * @param string $string
     * @return string
     */
    public static function lastPart(string $delimiter, string $string): string
    {
        $tmp = explode($delimiter, $string);
        return array_pop($tmp);
    }


    /**
     * Реализация mb_ucfirst, если её нет
     *
     * @param string $string
     * @param string $enc
     * @return string
     */
    public static function mbUcFirst(string $string, string $enc = 'utf-8'): string
    {
        $string = mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc)
            . mb_substr(
                $string,
                1,
                mb_strlen($string, $enc) - 1,
                $enc
            );
        return $string;
    }

    /**
     * Реализация mb_lcfirst, если её нет
     *
     * @param string $string
     * @param string $enc
     * @return string
     */
    public static function mbLcFirst(string $string, string $enc = 'utf-8'): string
    {
        $string = mb_strtolower(mb_substr($string, 0, 1, $enc), $enc)
            . mb_substr(
                $string,
                1,
                mb_strlen($string, $enc) - 1,
                $enc
            );
        return $string;
    }
}
