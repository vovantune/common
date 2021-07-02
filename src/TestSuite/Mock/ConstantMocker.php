<?php
declare(strict_types=1);

namespace ArtSkills\TestSuite\Mock;

use ArtSkills\Traits\Library;
use Exception;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Мокалка констант в классах
 */
class ConstantMocker
{
    use Library;

    /**
     * Список мокнутых констант
     *
     * @var array<string, string>
     */
    private static $_constantList = [];

    /**
     * Заменяем значение константы
     *
     * @param string|null $className
     * @param string $constantName
     * @param mixed $newValue
     * @throws AssertionFailedError|Exception
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     */
    public static function mock(?string $className, string $constantName, $newValue): void
    {
        if ($className !== null) {
            $fullName = $className . '::' . $constantName;
        } else {
            $fullName = $constantName;
        }
        // phpcs:ignore
        $origValue = @constant($fullName);
        if ($origValue === null) {
            MethodMocker::fail('Constant ' . $fullName . ' is not defined!');
        }
        if (isset(self::$_constantList[$fullName])) {
            MethodMocker::fail('Constant ' . $fullName . ' is already mocked!');
        }

        self::$_constantList[$fullName] = $origValue;
        if (!runkit_constant_redefine($fullName, $newValue)) {
            MethodMocker::fail("Can't redefine constant $fullName!");    // @codeCoverageIgnore
        }
    }

    /**
     * Возвращаем все обратно
     */
    public static function restore(): void
    {
        foreach (self::$_constantList as $name => $origValue) {
            runkit_constant_redefine($name, $origValue);
        }
        self::$_constantList = [];
    }
}
