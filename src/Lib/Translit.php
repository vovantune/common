<?php
declare(strict_types=1);

namespace ArtSkills\Lib;

use ArtSkills\Traits\Library;

class Translit
{
    use Library;

    /**
     * Переводим строку в транслит
     *
     * @param string $string
     * @return string
     */
    public static function transliterate(string $string): string
    {
        $translit = [
            "А" => "A",
            "Б" => "B",
            "В" => "V",
            "Г" => "G",
            "Д" => "D",
            "Е" => "E",
            "Ё" => "YO",
            "Ж" => "J",
            "З" => "Z",
            "И" => "I",
            "Й" => "Y",
            "К" => "K",
            "Л" => "L",
            "М" => "M",
            "Н" => "N",
            "О" => "O",
            "П" => "P",
            "Р" => "R",
            "С" => "S",
            "Т" => "T",
            "У" => "U",
            "Ф" => "F",
            "Х" => "H",
            "Ц" => "TS",
            "Ч" => "CH",
            "Ш" => "SH",
            "Щ" => "SCH",
            "Ъ" => "",
            "Ы" => "YI",
            "Ь" => "",
            "Э" => "E",
            "Ю" => "YU",
            "Я" => "YA",
            "а" => "a",
            "б" => "b",
            "в" => "v",
            "г" => "g",
            "д" => "d",
            "е" => "e",
            "ё" => "yo",
            "ж" => "j",
            "з" => "z",
            "и" => "i",
            "й" => "y",
            "к" => "k",
            "л" => "l",
            "м" => "m",
            "н" => "n",
            "о" => "o",
            "п" => "p",
            "р" => "r",
            "с" => "s",
            "т" => "t",
            "у" => "u",
            "ф" => "f",
            "х" => "h",
            "ц" => "ts",
            "ч" => "ch",
            "ш" => "sh",
            "щ" => "sch",
            "ъ" => "y",
            "ы" => "yi",
            "ь" => "",
            "э" => "e",
            "ю" => "yu",
            "я" => "ya",
        ];
        return strtr($string, $translit);
    }

    /**
     * Формируем псевдоним для названия
     *
     * @param string $string
     * @return string
     */
    public static function generateUrlAlias(string $string): string
    {
        $res = preg_replace('/_+/', '_', preg_replace('/[^0-9a-zA-Z]/', '_', strtolower(self::transliterate(trim($string)))));
        if (mb_substr($res, mb_strlen($res, 'utf-8') - 1, 1, 'utf-8') == '_') {
            $res = mb_substr($res, 0, mb_strlen($res, 'utf-8') - 1, 'utf-8');
        }
        return $res;
    }
}
