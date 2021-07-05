<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\TestSuite\Mock\Fixture;

use Exception;

const SINGLE_TEST_CONST = '666';
/**
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 * @SuppressWarnings(PHPMD.MethodMix)
 */
class MockTestFixture
{
    const TEST_CONSTANT = 123;
    const CLASS_CONST_NAME = 'TEST_CONSTANT';
    const GLOBAL_CONST_NAME = __NAMESPACE__ . '\SINGLE_TEST_CONST';
    /**
     * для тестов
     *
     * @var string
     */
    protected $_protectedProperty = 'testProtected';

    /**
     * для тестов
     *
     * @var string
     */
    private static $_privateProperty = 'testPrivateStatic';

    /**
     * для тестов
     *
     * @var string
     */
    private static $_otherProperty = 'testPrivateStatic';

    /**
     * Константа
     *
     * @return int
     */
    public static function getConst(): int
    {
        return self::TEST_CONSTANT;
    }

    /**
     * Константа, к которой обращаются через static
     *
     * @return int
     */
    public static function getConstStatic(): int
    {
        return static::TEST_CONSTANT;
    }


    /**
     * Тестовый метод
     *
     * @return string
     */
    public static function staticFunc(): string
    {
        return 'original public static';
    }

    /**
     * Много вариантов возвращаемых значений
     *
     * @return mixed
     */
    public static function staticFuncMixedResult()
    {
        return '';
    }

    /**
     * Тестовый метод
     *
     * @return string
     */
    public function publicFunc(): string
    {
        return 'original public';
    }

    /**
     * Тестовый метод
     *
     * @return string
     */
    private function _privateFunc(): string
    {
        return 'original private';
    }

    /**
     * Тестовый метод
     *
     * @return string
     */
    private static function _privateStaticFunc(): string
    {
        return 'original private static';
    }

    /**
     * Тестовый метод
     *
     * @return string
     */
    protected function _protectedFunc(): string
    {
        return 'original protected';
    }

    /**
     * Тестовый метод
     *
     * @return string
     */
    protected static function _protectedStaticFunc(): string
    {
        return 'original protected static';
    }

    /**
     * Вызов для проверки _protectedFunc
     *
     * @return string
     */
    public function callProtected(): string
    {
        return $this->_protectedFunc();
    }

    /**
     * Вызов для проверки _privateFunc
     *
     * @return string
     */
    public function callPrivate(): string
    {
        return $this->_privateFunc();
    }

    /**
     * Вызов для проверки _privateStaticFunc
     *
     * @return string
     */
    public static function callPrivateStatic(): string
    {
        return self::_privateStaticFunc();
    }

    /**
     * Вызов для проверки _protectedStaticFunc
     *
     * @return string
     */
    public static function callProtectedStatic(): string
    {
        return self::_protectedStaticFunc();
    }

    /**
     * Тестовый метод
     *
     * @return string
     */
    public function methodNoArgs(): string
    {
        return 'original no args';
    }

    /**
     * Тестовый метод
     *
     * @param mixed $first
     * @param mixed $second
     * @return string
     */
    public function methodArgs($first, $second): string
    {
        return $first . ' ' . $second;
    }

    /**
     * Тестовый метод
     *
     * @param mixed $first
     * @param mixed $second
     * @return string
     */
    public static function staticMethodArgs($first, $second): string
    {
        return 'static ' . $first . ' ' . $second;
    }

    /**
     * Тестовый метод
     *
     * @param string $arg
     * @return string
     */
    protected function _protectedArgs(string $arg): string
    {
        return 'protected args ' . $arg;
    }


    /**
     * для тестов в переопределением
     *
     * @return string
     */
    protected static function _redefinedStaticFunc(): string
    {
        return 'parent protected static';
    }

    /**
     * для тестов в переопределением
     *
     * @return string
     */
    protected function _redefinedFunc(): string
    {
        return 'parent protected';
    }

    /**
     * вызов метода для тестов в переопределением
     *
     * @param bool $isStatic
     * @param bool $isRedefined
     * @param string $callType
     * @return string
     * @throws Exception
     */
    public function callParent(bool $isStatic, bool $isRedefined, string $callType): string
    {
        $methodName = self::getInheritTestFuncName($isStatic, $isRedefined);
        if ($isStatic) {
            if ($callType == 'self') {
                return self::$methodName();
            } elseif ($callType == 'static') {
                return static::$methodName();
            } else {
                throw new Exception('bad call type');
            }
        } else {
            return $this->$methodName();
        }
    }

    /**
     * название метода для тестов в переопределением
     *
     * @param bool $isStatic
     * @param bool $isRedefined
     * @return string
     */
    public static function getInheritTestFuncName(bool $isStatic, bool $isRedefined): string
    {
        if ($isRedefined) {
            $methodName = '_redefined';
        } else {
            $methodName = '_protected';
        }
        if ($isStatic) {
            $methodName .= 'Static';
        }
        $methodName .= 'Func';
        return $methodName;
    }

    /**
     * Функция с типизированными и обязательными параметрами и передачей по ссылке
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param mixed $byRefParam
     * @param MockTestFixture $objParam
     * @param array $arrayParam
     * @param float $typedParam
     * @param string|null $nullableParam
     * @param mixed $requiredParam
     * @param array $mayBeNotArray
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public static function complexParams(
        &$byRefParam,
        MockTestFixture $objParam,
        array $arrayParam,
        float $typedParam,
        ?string $nullableParam,
        $requiredParam,
        $mayBeNotArray = []
    ): void {
        // noop
    }


    /**
     * Функция с дефолтными значениями
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param array $arrayParam
     * @param float $floatParam
     * @param string $stringParam
     * @param bool $boolParam
     * @param null $nullParam
     * @return array
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public static function defaultValues(
        array $arrayParam = ['a' => [null]],
        float $floatParam = 2.5,
        string $stringParam = 'asd',
        bool $boolParam = true,
        $nullParam = null
    ): array {
        return [];
    }

    /**
     * Функция с вариадиком
     *
     * @param int ...$variadicParam
     * @return int[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function variadicParam(int ...$variadicParam)
    {
        return [];
    }

    /**
     * метод с возвращаемым значением
     *
     * @return int
     */
    public static function returnInt(): int
    {
        return 1;
    }

    /**
     * метод с возвращаемым значением nullable
     *
     * @return int|null
     */
    public static function returnNullable(): ?int
    {
        return 1;
    }

    /**
     * Подмена метода без возвращаемого результата
     */
    public static function voidMock(): void
    {
        // noop
    }
}
