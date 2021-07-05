<?php
declare(strict_types=1);

namespace ArtSkills\Lib;

class Misc
{
    /**
     * Разбить полное название класса на неймспейс и класс
     *
     * @param string $class
     * @param bool $onlyClass
     * @return string|string[]
     */
    public static function namespaceSplit(string $class, bool $onlyClass = false)
    {
        $pos = strrpos($class, '\\');
        if ($pos === false) {
            $res = ['', $class];
        } else {
            $res = [substr($class, 0, $pos), substr($class, $pos + 1)];
        }
        if ($onlyClass) {
            return $res[1];
        } else {
            return $res;
        }
    }

    /**
     * Соединить путь через DS
     *
     * @param string[] ...$parts
     * @return string
     */
    public static function implodeDs(...$parts): string
    {
        return trim(implode(DS, $parts));
    }
}
