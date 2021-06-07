<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\TestSuite\Mock;

use ArtSkills\TestSuite\Mock\MethodMockerEntity;
use ArtSkills\Test\TestCase\TestSuite\Mock\Fixture\MockTestChildFixture;
use ArtSkills\Test\TestCase\TestSuite\Mock\Fixture\MockTestFixture;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ArtSkills\TestSuite\Mock\MethodMockerEntity
 */
class MethodMockerEntityTest extends TestCase
{
    /**
     * тестируемые методы
     *
     * @return array
     */
    public function mockMethodsProvider(): array
    {
        return [
            ['publicFunc', false, false, false],
            ['staticFunc', true, false, false],
            ['_privateFunc', false, true, false],
            ['_privateStaticFunc', true, true, false],
            ['_protectedFunc', false, false, true],
            ['_protectedStaticFunc', true, false, true],
        ];
    }

    /**
     * тесты моков всех сочетаний public/protected/private static/non-static
     *
     * @dataProvider mockMethodsProvider
     * @param string $methodName
     * @param bool $isStatic
     * @param bool $isPrivate
     * @param bool $isProtected
     */
    public function testSimpleMocks(string $methodName, bool $isStatic, bool $isPrivate, bool $isProtected)
    {
        if ($isStatic) {
            $instance = null;
        } else {
            $instance = new MockTestFixture();
        }
        $originalResult = $this->_callFixtureMethod($instance, $isPrivate, $isProtected);
        $mockResult = 'mock ' . $methodName;
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, $methodName, false, function () use (
            $mockResult
        ) {
            return $mockResult;
        });
        self::assertEquals($mockResult, $this->_callFixtureMethod($instance, $isPrivate, $isProtected));
        unset($mock);

        self::assertEquals($originalResult, $this->_callFixtureMethod($instance, $isPrivate, $isProtected));
    }

    /**
     * Вызов нужного метода
     *
     * @param MockTestFixture $instance
     * @param bool $isPrivate
     * @param bool $isProtected
     * @return string
     */
    private function _callFixtureMethod(?MockTestFixture $instance, bool $isPrivate, bool $isProtected): string
    {
        if ($isPrivate) {
            if (empty($instance)) {
                return MockTestFixture::callPrivateStatic();
            } else {
                return $instance->callPrivate();
            }
        } elseif ($isProtected) {
            if (empty($instance)) {
                return MockTestFixture::callProtectedStatic();
            } else {
                return $instance->callProtected();
            }
        } else {
            if (empty($instance)) {
                return MockTestFixture::staticFunc();
            } else {
                return $instance->publicFunc();
            }
        }
    }

    /**
     * Мок на несуществующий класс
     */
    public function testMockBadClass()
    {
        $this->expectExceptionMessage("class \"badClass\" does not exist!");
        $this->expectException(AssertionFailedError::class);
        new MethodMockerEntity('mockid', 'badClass', '_protectedFunc');
    }

    /**
     * Мок на несуществующий метод
     */
    public function testMockBadMethod()
    {
        $this->expectExceptionMessage("method \"badMethod\" in class \"ArtSkills\Test\TestCase\TestSuite\Mock\Fixture\MockTestFixture\" does not exist!");
        $this->expectException(AssertionFailedError::class);
        new MethodMockerEntity('mockid', MockTestFixture::class, 'badMethod');
    }

    /**
     * Мок с кривым экшном
     */
    public function testMockBadAction()
    {
        $this->expectExceptionMessage("action must be a string, a Closure or a null");
        $this->expectException(AssertionFailedError::class);
        new MethodMockerEntity('mockid', MockTestFixture::class, 'staticFunc', false, 123);
    }

    /**
     * Восстановленный мок, для тестов того, что с ним ничего нельзя сделать
     *
     * @return MethodMockerEntity
     */
    private function _getRestoredMock(): MethodMockerEntity
    {
        $mock = $this->_getMock();
        $mock->expectCall(0);
        $mock->restore();
        return $mock;
    }

    /**
     * Мок вернули, а его конфигурируют
     */
    public function testRestoredExpectCall()
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->expectCall();
    }

    /**
     * Мок вернули, а его конфигурируют
     */
    public function testRestoredExpectArgs()
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->expectArgs('asd');
    }

    /**
     * Мок вернули, а его конфигурируют
     */
    public function testRestoredExpectNoArgs()
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->expectNoArgs();
    }

    /**
     * Мок вернули, а его конфигурируют
     */
    public function testRestoredExpectSomeArgs()
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->expectSomeArgs(['asd']);
    }

    /**
     * Мок вернули, а его конфигурируют
     */
    public function testRestoredExpectArgsList()
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->expectArgsList([false]);
    }

    /**
     * Мок вернули, а его конфигурируют
     */
    public function testRestoredWillReturnValue()
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->willReturnValue(true);
    }

    /**
     * Мок вернули, а его конфигурируют
     */
    public function testRestoredWillReturnAction()
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->willReturnAction(function ($args) {
            return $args;
        });
    }

    /**
     * Мок вернули, а его вызывают
     */
    public function testRestoredDoAction()
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->doAction([]);
    }

    /**
     * Мок вернули, а ему задают доп. перем-ю
     */
    public function testRestoredSetAdditionalVar()
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->setAdditionalVar(123);
    }

    /**
     * Мок вернули, а ему задают ексепшн
     */
    public function testRestoredSetException()
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->willThrowException('asd');
    }

    /**
     * Мок вернули, а ему задают возвращаемые значения
     */
    public function testRestoredReturnList()
    {
        $this->expectExceptionMessage("mock entity is restored!");
        $this->expectException(AssertionFailedError::class);
        $this->_getRestoredMock()->willReturnValueList([true]);
    }


    /**
     * Мок для тестов
     *
     * @return MethodMockerEntity
     */
    private function _getMock(): MethodMockerEntity
    {
        return new MethodMockerEntity('mockid', MockTestFixture::class, 'staticFunc', false);
    }

    /**
     * Вызывали ли мок хотя бы раз
     */
    public function testMockCallCheck()
    {
        $this->expectExceptionMessage("is not called!");
        $this->expectException(AssertionFailedError::class);
        $this->_getMock();
    }

    /**
     * Пустой список ожидаемых аргументов
     */
    public function testExpectArgsEmpty()
    {
        $this->expectExceptionMessage("method expectArgs() requires at least one arg!");
        $this->expectException(AssertionFailedError::class);
        $mock = $this->_getMock()->expectCall(0);
        $mock->expectArgs();
    }

    /**
     * Список ожидаемых аргументов - null
     */
    public function testExpectArgsNull()
    {
        $mock = $this->_getMock()->expectCall(0);
        $mock->expectArgs(null);
        // При значении null не вылетел ексепшн с проверки на пустоту
        self::assertTrue(true);
    }

    /**
     * Пустой список ожидаемых аргументов
     */
    public function testExpectSomeArgsEmpty()
    {
        $this->expectExceptionMessage("empty arguments list for expectSomeArgs()");
        $this->expectException(AssertionFailedError::class);
        $mock = $this->_getMock()->expectCall(0);
        $mock->expectSomeArgs([]);
    }


    /**
     * Список пуст
     */
    public function testExpectedArgsListEmpty()
    {
        $this->expectExceptionMessage("empty args list in expectArgsList()");
        $this->expectException(AssertionFailedError::class);
        $mock = $this->_getMock()->expectCall(0);
        $mock->expectArgsList([]);
    }

    /**
     * null в списке
     */
    public function testExpectedArgsListNull()
    {
        $this->expectExceptionMessage("args list item 1: expected not empty array or false");
        $this->expectException(AssertionFailedError::class);
        $mock = $this->_getMock()->expectCall(0);
        $mock->expectArgsList([false, null]);
    }

    /**
     * true в списке
     */
    public function testExpectedArgsListTrue()
    {
        $this->expectExceptionMessage("args list item 2: expected not empty array or false");
        $this->expectException(AssertionFailedError::class);
        $mock = $this->_getMock()->expectCall(0);
        $mock->expectArgsList([false, [1], true]);
    }

    /**
     * пустой массив в списке
     */
    public function testExpectedArgsListEmptyArr()
    {
        $this->expectExceptionMessage("args list item 0: expected not empty array or false");
        $this->expectException(AssertionFailedError::class);
        $mock = $this->_getMock()->expectCall(0);
        $mock->expectArgsList([[]]);
    }

    /**
     * Для тестов наследования
     *
     * @return array
     */
    public function mockInheritedProvider(): array
    {
        return [
            /*
            тип вызова,
            метод переопределён?,
            замокать класс-наследник? (или родитель),
            вызывающий метод определён в наследнике? (или в родителе),
            результат - замокан? (или вернётся исходный)
            */
            ['this', 'notRedefined', 'mockParent', 'callFromParent', 'resultMocked'],
            ['this', 'notRedefined', 'mockParent', 'callFromChild', 'resultMocked'],
            //['this', 'notRedefined', 'mockChild', 'callFromParent', 'resultMocked'],
            //['this', 'notRedefined', 'mockChild', 'callFromChild', 'resultMocked'],
            ['this', 'isRedefined', 'mockParent', 'callFromParent', 'resultOriginal'],
            ['this', 'isRedefined', 'mockParent', 'callFromChild', 'resultOriginal'],
            ['this', 'isRedefined', 'mockChild', 'callFromParent', 'resultMocked'],
            ['this', 'isRedefined', 'mockChild', 'callFromChild', 'resultMocked'],


            ['self', 'notRedefined', 'mockParent', 'callFromParent', 'resultMocked'],
            ['self', 'notRedefined', 'mockParent', 'callFromChild', 'resultMocked'],
            //['self', 'notRedefined', 'mockChild', 'callFromParent', 'resultOriginal'],
            //['self', 'notRedefined', 'mockChild', 'callFromChild', 'resultMocked'],
            ['self', 'isRedefined', 'mockParent', 'callFromParent', 'resultMocked'],
            ['self', 'isRedefined', 'mockParent', 'callFromChild', 'resultOriginal'],
            ['self', 'isRedefined', 'mockChild', 'callFromParent', 'resultOriginal'],
            ['self', 'isRedefined', 'mockChild', 'callFromChild', 'resultMocked'],

            ['static', 'notRedefined', 'mockParent', 'callFromParent', 'resultMocked'],
            ['static', 'notRedefined', 'mockParent', 'callFromChild', 'resultMocked'],
            //['static', 'notRedefined', 'mockChild', 'callFromParent', 'resultMocked'],
            //['static', 'notRedefined', 'mockChild', 'callFromChild', 'resultMocked'],
            ['static', 'isRedefined', 'mockParent', 'callFromParent', 'resultOriginal'],
            ['static', 'isRedefined', 'mockParent', 'callFromChild', 'resultOriginal'],
            ['static', 'isRedefined', 'mockChild', 'callFromParent', 'resultMocked'],
            ['static', 'isRedefined', 'mockChild', 'callFromChild', 'resultMocked'],

            ['parent', 'notRedefined', 'mockParent', 'callFromChild', 'resultMocked'],
            //['parent', 'notRedefined', 'mockChild', 'callFromChild', 'resultOriginal'],
            ['parent', 'isRedefined', 'mockParent', 'callFromChild', 'resultMocked'],
            ['parent', 'isRedefined', 'mockChild', 'callFromChild', 'resultOriginal'],
        ];
    }

    /**
     * тесты моков с наследованием
     *
     * @dataProvider mockInheritedProvider
     * @param string $callType тип вызова
     * @param string $redefinedParam метод переопределён?
     * @param string $mockParam замокать класс-наследник? (или родитель)
     * @param string $callParam вызываемый метод определён в наследнике? (или в родителе)
     * @param string $resultParam результат - замокан? (или вернётся исходный)
     */
    public function testInheritedMocks($callType, $redefinedParam, $mockParam, $callParam, $resultParam)
    {
        $callChild = ($callParam === 'callFromChild');
        $isRedefined = ($redefinedParam === 'isRedefined');
        $mockChild = ($mockParam === 'mockChild');

        if (!$callChild && ($callType === 'parent')) {
            self::fail('бред');
        }
        $isStatic = ($callType !== 'this');
        $methodName = MockTestChildFixture::getInheritTestFuncName($isStatic, $isRedefined);
        if ($mockChild) {
            $mockClass = MockTestChildFixture::class;
        } else {
            $mockClass = MockTestFixture::class;
        }

        $testObject = new MockTestChildFixture();
        $originalResult = $testObject->call($callChild, $isStatic, $isRedefined, $callType);

        $mockResult = 'mock ' . $methodName . ' ' . $callType . ' ' . (int)$mockChild . ' ' . (int)$callChild;
        $mock = new MethodMockerEntity('mockid', $mockClass, $methodName, false, "return '$mockResult';");

        if ($resultParam === 'resultMocked') {
            $expectedResult = $mockResult;
        } else {
            $expectedResult = $originalResult;
        }
        $actualResult = $testObject->call($callChild, $isStatic, $isRedefined, $callType);

        self::assertEquals($expectedResult, $actualResult);
        unset($mock);

        self::assertEquals($originalResult, $testObject->call($callChild, $isStatic, $isRedefined, $callType));
    }


    /**
     * мок не отнаследованного protected метода в классе-наследнике
     */
    public function testProtectedMockChild()
    {
        $originalResult = MockTestChildFixture::callChildOnlyProtected();
        $mockResult = 'mock child only protected';
        $mock = new MethodMockerEntity('mockid', MockTestChildFixture::class, '_childOnlyFunc', false, "return '$mockResult';");
        self::assertEquals($mockResult, MockTestChildFixture::callChildOnlyProtected());
        unset($mock);

        self::assertEquals($originalResult, MockTestChildFixture::callChildOnlyProtected());
    }

    /**
     * нельзя просниффать при полной подмене
     */
    public function testSniff()
    {
        $this->expectExceptionMessage("Sniff mode does not support full mock");
        $this->expectException(AssertionFailedError::class);
        new MethodMockerEntity('mockid', MockTestFixture::class, 'staticFunc', true, function () {
            return 'sniff';
        });
    }


    /**
     * нельзя мокать отнаследованное через анонимные функции
     */
    public function testMockInheritedClosure()
    {
        $this->expectExceptionMessage("can't mock inherited method _redefinedFunc as Closure");
        $this->expectException(AssertionFailedError::class);
        new MethodMockerEntity('mockid', MockTestChildFixture::class, '_redefinedFunc', false, function () {
            return 'mock';
        });
    }


    /**
     * нельзя мокать отнаследованное непереопределённое
     */
    public function testMockInheritedNotRedeclared()
    {
        $this->expectExceptionMessage("method staticFunc is declared in parent class");
        $this->expectException(AssertionFailedError::class);
        new MethodMockerEntity('mockid', MockTestChildFixture::class, 'staticFunc', false, 'return 123;');
    }

    /**
     * При переопределении метода его прототип должен оставаться тем же,
     * чтобы не было конфликта с наследниками
     * Должны сохраняться: тип, передача по ссылке и количество обязательных параметров
     * @SuppressWarnings(PHPMD.UnusedLocalVariable) переменная нужна, чтоб объект сразу же не уничтожился
     */
    public function testStrictParams()
    {
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'complexParams', false, 'return 123;');
        // при одиночном запуске теста, если что-то не так, будет strict error
        MockTestChildFixture::staticFunc();
        self::assertTrue(true); // всё хорошо, скрипт не упал
    }

    /**
     * Провайдер для проверок определения типов
     *
     * @return array
     */
    public function paramDeclareProvider()
    {
        $objParam = new MockTestFixture();
        $arrParam = [];
        $floatParam = 1.1;
        $stringParam = 'asd';
        $requiredParam = 1;
        return [
            0 => [
                [
                    'params' => [true, $objParam, $arrParam, $floatParam, $stringParam],
                    'errorClass' => \ArgumentCountError::class,
                    'errorMsg' => 'Too few arguments',
                ],
            ],
            1 => [
                [
                    'params' => [true, 1, $arrParam, $floatParam, $stringParam, $requiredParam],
                    'errorClass' => \TypeError::class,
                    'errorMsg' => 'must be an instance of ' . MockTestFixture::class,
                ],
            ],
            2 => [
                [
                    'params' => [true, $objParam, 1, $floatParam, $stringParam, $requiredParam],
                    'errorClass' => \TypeError::class,
                    'errorMsg' => 'must be of the type array',
                ],
            ],
            3 => [
                [
                    'params' => [true, $objParam, $arrParam, [], $stringParam, $requiredParam],
                    'errorClass' => \TypeError::class,
                    'errorMsg' => 'must be of the type float',
                ],
            ],
            4 => [
                [
                    'params' => [true, $objParam, $arrParam, $floatParam, [], $requiredParam],
                    'errorClass' => \TypeError::class,
                    'errorMsg' => 'must be of the type string',
                ],
            ],
            5 => [
                [
                    // тут всё ок
                    'params' => [true, $objParam, $arrParam, $floatParam, $stringParam, $requiredParam],
                    'errorClass' => '',
                    'errorMsg' => '',
                ],
            ],
            6 => [
                [
                    // тут всё ок
                    'params' => [true, $objParam, $arrParam, $floatParam, null, $requiredParam],
                    'errorClass' => '',
                    'errorMsg' => '',
                ],
            ],
            // отсутствует тест того, что передача параметра по ссылке сохраняется
        ];
    }

    /**
     * Ещё один тест, проверяющий объявление параметров
     * Должны сохраняться: тип, передача по ссылке и количество обязательных параметров
     * @SuppressWarnings(PHPMD.UnusedLocalVariable) переменная нужна, чтоб объект сразу же не уничтожился
     *
     * @dataProvider paramDeclareProvider
     */
    public function testParamDeclare(array $testData)
    {
        ['params' => $params, 'errorClass' => $errorClass, 'errorMsg' => $errorMsg] = $testData;
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'complexParams', false, "return 123;");
        $refParam = 1;
        $useRefParam = array_shift($params);
        try {
            MockTestFixture::complexParams($refParam, ...$params);
            $error = null;
        } catch (\Throwable $e) {
            $error = $e;
        }
        if (empty($errorClass)) {
            self::assertEquals(null, $error);
        } else {
            self::assertInstanceOf($errorClass, $error);
            self::assertContains($errorMsg, $error->getMessage());
        }
    }


    /**
     * тест того, что дефолтные значения сохраняются
     * @SuppressWarnings(PHPMD.UnusedLocalVariable) переменная нужна, чтоб объект сразу же не уничтожился
     */
    public function testDefaultValues()
    {
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'defaultValues', false, 'return get_defined_vars();');
        $expectedResult = [
            'arrayParam' => ['a' => [null]],
            'floatParam' => 2.5,
            'stringParam' => 'asd',
            'boolParam' => true,
            'nullParam' => null,
        ];
        $result = MockTestFixture::defaultValues();
        self::assertEquals($expectedResult, $result);
    }

    /**
     * variadic параметры тоже должны правильно обрабатываться
     * без ... будет ошибка
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testVariadicParam()
    {
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'variadicParam', false, 'return get_defined_vars();'); // переменная нужна, чтоб объект сразу же не уничтожился
        self::assertEquals(['variadicParam' => []], MockTestFixture::variadicParam());
        self::assertEquals(['variadicParam' => [1]], MockTestFixture::variadicParam(1));
        self::assertEquals(['variadicParam' => [1, 2]], MockTestFixture::variadicParam(1, 2));
    }

    /**
     * variadic с типом
     */
    public function testVariadicParamType()
    {
        $this->expectExceptionMessage("must be of the type int");
        $this->expectException(\TypeError::class);
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'variadicParam', false, 'return get_defined_vars();');
        MockTestFixture::variadicParam('asd');
    }


    /**
     * Сохранение типа возвращаемого значения
     */
    public function testReturnTypeError()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("must be of the type int, null returned");
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'returnInt', false, 'return null;');
        MockTestFixture::returnInt();
    }

    /**
     * Сохранение типа возвращаемого значения
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testReturnTypeGood()
    {
        $returnInt = 4;
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'returnInt', false, "return $returnInt;");
        $mock2 = new MethodMockerEntity('mockid', MockTestFixture::class, 'returnNullable', false, 'return null;');
        self::assertEquals($returnInt, MockTestFixture::returnInt());
        self::assertEquals(null, MockTestFixture::returnNullable());
    }

    /**
     * Сохранение типа возвращаемого значения
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testReturnTypeNullableError()
    {
        $this->expectExceptionMessage("must be of the type int or null, array returned");
        $this->expectException(\TypeError::class);
        $mock = new MethodMockerEntity('mockid', MockTestFixture::class, 'returnNullable', false, 'return [];');
        MockTestFixture::returnNullable();
    }
}
