<?php
declare(strict_types=1);

namespace ArtSkills\TestSuite\Mock;

use ReflectionClass;
use \ReflectionMethod;

/**
 * Мок метода
 */
class MethodMockerEntity
{
    /**
     * префикс при переименовании метода
     */
    const RENAME_PREFIX = '___rk_';

    /**
     * Метод должен быть вызван хотя бы раз
     */
    const EXPECT_CALL_ONCE = -1;

    /**
     * ID текущего мока в стеке MethodMocker
     *
     * @var string
     */
    private $_id = '';

    /**
     * Файл, в котором мокнули
     *
     * @var string
     */
    private $_callerFile = '';

    /**
     * Строка вызова к MethodMocker::mock
     *
     * @var int
     */
    private $_callerLine = 0;

    /**
     * Класс метода
     *
     * @var string
     */
    private $_class = '';

    /**
     * Мокаемый метод
     *
     * @var string
     */
    private $_method = '';

    /**
     * Новое подменяемое событие
     *
     * @var callable|string|null
     */
    private $_action = null;

    /**
     * Сколько раз ожидается вызов функции
     *
     * @var int
     */
    private $_expectedCallCount = self::EXPECT_CALL_ONCE;

    /**
     * Предполагаемые аргументы
     *
     * @var null|array
     */
    private $_expectedArgs = null;

    /**
     * Часть предполагаемых аргументов
     *
     * @var null|array
     */
    private $_expectedArgsSubset = null;

    /**
     * Предполагаемые аргументы на несколько вызовов
     *
     * @var null|array
     */
    private $_expectedArgsList = null;

    /**
     * Возвращаемый результат
     *
     * @var null|mixed
     */
    private $_returnValue = null;

    /**
     * Возвращаемое событие
     *
     * @var null|callable
     */
    private $_returnAction = null;

    /**
     * Был ли вызов данного мока
     *
     * @var bool
     */
    private $_isCalled = false;

    /**
     * Кол-во вызовов данного мока
     *
     * @var int
     */
    private $_callCounter = 0;

    /**
     * Мок отработал и все вернул в первоначальный вид
     *
     * @var bool
     */
    private $_mockRestored = false;

    /**
     * Функция не подменяется, а снифается
     *
     * @var bool
     */
    private $_sniffMode = false;

    /**
     * Дополнительная переменная, которую можно использовать в _returnAction
     *
     * @var mixed
     */
    private $_additionalVar = null;

    /**
     * Кидаемый ексепшн
     *
     * @var array {$message: string, $class: string}
     */
    private $_exceptionConf = null;

    /**
     * Список возвращаемых значений
     *
     * @var null|array
     */
    private $_returnValueList = null;

    /**
     * Задано ли возвращение результата. Нужно для того, что еще и вернуть void для PHP7
     *
     * @var bool
     */
    private $_isReturnDataSet = false;

    /**
     * Полная подмена или нет
     *
     * @var bool
     */
    private $_isFullMock = false;

    /**
     * MethodMockerEntity constructor.
     * Не рекомендуется создавать непосредственно, лучше через MethodMocker
     * При непосредственном создании доступна только полная подмена
     * При полной подмене не доступны функции expectCall(), expectArgs() и willReturn()
     * и $this и self обращаются к подменяемому объекту/классу
     * Неполная подмена делается только через MethodMocker
     * доступен весь функционал, $this и self берутся из вызываемого контекста
     *
     * @throws \PHPUnit\Framework\AssertionFailedError|\Exception
     * @param string $mockId
     * @param string $className
     * @param string $methodName
     * @param bool $sniffMode
     * @param null|callable|string $newAction - полная подмена
     */
    public function __construct(
        string $mockId,
        string $className,
        string $methodName,
        bool $sniffMode = false,
        $newAction = null
    ) {
        $calledFrom = debug_backtrace();
        $this->_callerFile = isset($calledFrom[1]['file']) ? $calledFrom[1]['file'] : $calledFrom[0]['file'];
        $this->_callerLine = isset($calledFrom[1]['line']) ? $calledFrom[1]['line'] : $calledFrom[0]['line'];

        $this->_id = $mockId;
        $this->_class = $className;
        $this->_method = $methodName;
        $this->_action = $newAction;
        $this->_sniffMode = $sniffMode;
        $this->_isFullMock = !empty($newAction);
        if ($this->_isFullMock && $sniffMode) {
            $this->_fail('Sniff mode does not support full mock');
        }
        $this->_checkCanMock();
        $this->_mockOriginalMethod();
    }

    /**
     * Флаги, с которыми будет переопределять ранкит
     *
     * @param ReflectionMethod $reflectionMethod
     * @return int
     */
    private function _getRunkitFlags(ReflectionMethod $reflectionMethod): int
    {
        $flags = 0;
        if ($reflectionMethod->isPublic()) {
            $flags |= RUNKIT_ACC_PUBLIC;
        }
        if ($reflectionMethod->isProtected()) {
            $flags |= RUNKIT_ACC_PROTECTED;
        }
        if ($reflectionMethod->isPrivate()) {
            $flags |= RUNKIT_ACC_PRIVATE;
        }
        if ($reflectionMethod->isStatic()) {
            $flags |= RUNKIT_ACC_STATIC;
        }
        return $flags;
    }

    /**
     * Список параметров, чтоб переопределение работало правильно
     *
     * @param ReflectionMethod $reflectionMethod
     * @return string
     * @throws \PHPUnit\Framework\AssertionFailedError|\Exception
     */
    private function _getMethodParameters(ReflectionMethod $reflectionMethod): string
    {
        $arguments = [];
        $parameters = (array)$reflectionMethod->getParameters();
        /** @var \ReflectionParameter $parameter */
        foreach ($parameters as $parameter) {
            $paramDeclaration = '$' . $parameter->getName();
            if ($parameter->isPassedByReference()) {
                $paramDeclaration = '&' . $paramDeclaration;
            }
            if ($parameter->isVariadic()) {
                $paramDeclaration = '...' . $paramDeclaration;
            } elseif ($parameter->isOptional()) {
                $defaultValue = $parameter->getDefaultValue();
                $paramDeclaration .= ' = ' . var_export($defaultValue, true);
            }
            $type = $parameter->getType();
            if (!empty($type)) {
                $paramDeclaration = (string)$type->getName() . ' ' . $paramDeclaration;
                if ($type->allowsNull()) {
                    $paramDeclaration = '?' . $paramDeclaration;
                }
            }

            $arguments[$parameter->getPosition()] = $paramDeclaration;
        }
        return implode(', ', $arguments);
    }

    /**
     * Тип возвращаемого значения
     *
     * @param ReflectionMethod $reflectionMethod
     * @return string
     */
    private function _getReturnType(ReflectionMethod $reflectionMethod): string
    {
        $returnTypeDeclaration = '';
        $returnType = $reflectionMethod->getReturnType();
        if (!empty($returnType)) {
            $returnTypeDeclaration = ($returnType->allowsNull() ? '?' : '') . (string)$returnType->getName();
        }
        return $returnTypeDeclaration;
    }

    /**
     * Омечаем, что функция должна вызываться разово
     *
     * @return $this
     * @throws \PHPUnit\Framework\AssertionFailedError|\Exception
     */
    public function singleCall(): self
    {
        return $this->expectCall(1);
    }

    /**
     * Омечаем, что функция должна вызываться как минимум 1 раз
     *
     * @return $this
     * @throws \PHPUnit\Framework\AssertionFailedError|\Exception
     */
    public function anyCall(): self
    {
        return $this->expectCall(self::EXPECT_CALL_ONCE);
    }

    /**
     * Ограничение на количество вызовов данного мока
     *
     * @param int $times
     * @return $this
     * @throws \PHPUnit\Framework\AssertionFailedError|\Exception
     */
    public function expectCall($times = 1): self
    {
        $this->_checkNotRestored();
        $this->_expectedCallCount = $times;
        return $this;
    }

    /**
     * Устанавливаем ожидаемые аргументы, необходимо указать как минимум 1. Если данный метод не вызывать, то проверка
     * на аргументы не проводится.
     * Если нужно явно задать отсутствие аргументов, то нужно вызывать expectNoArgs()
     *
     * @param mixed ...$args
     * @return $this
     * @throws \PHPUnit\Framework\AssertionFailedError|\Exception
     */
    public function expectArgs(...$args): self
    {
        $this->_checkNotRestored();

        if (empty($args)) {
            $this->_fail('method expectArgs() requires at least one arg!');
        }

        $this->_unsetExpectArgs();
        $this->_expectedArgs = $args;

        return $this;
    }

    /**
     * Ожидается вызов метода без аргументов
     *
     * @return $this
     */
    public function expectNoArgs(): self
    {
        $this->_checkNotRestored();
        $this->_unsetExpectArgs();
        $this->_expectedArgs = false;
        return $this;
    }

    /**
     * Проверить только часть аргументов.
     *
     * @param array $argsSubset - массив с числовым индексом - номером аргумента.
     * @return $this
     */
    public function expectSomeArgs(array $argsSubset): self
    {
        $this->_checkNotRestored();

        if (empty($argsSubset)) {
            $this->_fail('empty arguments list for expectSomeArgs()');
        }

        $this->_unsetExpectArgs();
        $this->_expectedArgsSubset = $argsSubset;

        return $this;
    }

    /**
     * Ожидаемые аргументы на несколько вызовов.
     *
     * @param array $argsList - Массив списков(массивов) аргументов на каждый вызов.
     *                        Если ожидается вызов без аргументов, то вместо массива аргументов - false.
     * @return $this
     */
    public function expectArgsList(array $argsList): self
    {
        $this->_checkNotRestored();
        if (empty($argsList)) {
            $this->_fail('empty args list in expectArgsList()!');
        }
        foreach ($argsList as $key => $callArgs) {
            if ((!is_array($callArgs) && ($callArgs !== false)) || (is_array($callArgs) && empty($callArgs))) {
                $this->_fail('args list item ' . $key . ': expected not empty array or false');
            }
        }
        $this->_unsetExpectArgs();
        $this->_expectedArgsList = $argsList;
        return $this;
    }

    /**
     * Задает дополнительную переменную.
     *
     * @param mixed $var Новое значение дополнительной переменной
     * @return $this
     */
    public function setAdditionalVar($var): self
    {
        $this->_checkNotRestored();
        $this->_additionalVar = $var;
        return $this;
    }

    /**
     * Сброс ожидаемых значений
     */
    private function _unsetExpectArgs(): void
    {
        $this->_expectedArgs = null;
        $this->_expectedArgsList = null;
        $this->_expectedArgsSubset = null;
    }

    /**
     * Сброс возвращаемого действия
     */
    private function _unsetReturn(): void
    {
        $this->_isReturnDataSet = false;
        $this->_returnAction = null;
        $this->_returnValue = null;
        $this->_exceptionConf = null;
        $this->_returnValueList = null;
    }

    /**
     * Что вернет подменённая функция
     *
     * @param mixed $value
     * @return $this
     * @throws \PHPUnit\Framework\AssertionFailedError|\Exception
     */
    public function willReturnValue($value): self
    {
        $this->_checkNotRestored();
        $this->_unsetReturn();
        $this->_returnValue = $value;
        $this->_isReturnDataSet = true;
        return $this;
    }

    /**
     * Подменённая функция вернет результат функции $action(array Аргументы, [mixed Результат от оригинального метода])
     * Второй поараметр заполняется только в режиме снифа метода
     * Пример:
     * ->willReturnAction(function($args){
     *    return 'mocked: '.$args[0].' '.$args[1];
     * });
     *
     * @param callable $action
     * @return $this
     * @throws \PHPUnit\Framework\AssertionFailedError|\Exception
     */
    public function willReturnAction($action): self
    {
        $this->_checkNotRestored();
        $this->_unsetReturn();
        $this->_returnAction = $action;
        $this->_isReturnDataSet = true;
        return $this;
    }

    /**
     * Подменённая функция кинет ексепшн (по умолчанию - \Exception, можно задать класс вторым параметром)
     *
     * @param string $message
     * @param null|string $class
     * @return $this
     */
    public function willThrowException($message, $class = null): self
    {
        $this->_checkNotRestored();
        $this->_unsetReturn();
        $this->_exceptionConf = [
            'message' => $message,
            'class' => (($class === null) ? \Exception::class : $class),
        ];
        $this->_isReturnDataSet = true;
        return $this;
    }

    /**
     * Массив возвращаемых значений на несколько вызовов
     * (для случаев, когда один вызов тестируемого метода делает более одного вызова замоканного метода)
     *
     * @param array $valueList
     * @return $this
     */
    public function willReturnValueList(array $valueList): self
    {
        $this->_checkNotRestored();
        $this->_unsetReturn();
        $this->_returnValueList = $valueList;
        $this->_isReturnDataSet = true;
        return $this;
    }

    /**
     * Событие оригинальной функции
     *
     * @param array $args массив переданных аргументов к оригинальной функции
     * @param mixed $origMethodResult
     * @return mixed
     * @throws \PHPUnit\Framework\AssertionFailedError|\Exception
     */
    public function doAction(array $args, $origMethodResult = null)
    {
        $this->_checkNotRestored();
        $this->_incCounter();
        $this->_checkArgs($args);
        return $this->_getReturnValue($args, $origMethodResult);
    }

    /**
     * Увеличение счётчика вызовов и проверка кол-ва вызовов
     */
    private function _incCounter(): void
    {
        if (($this->_expectedCallCount > self::EXPECT_CALL_ONCE) && ($this->_callCounter >= $this->_expectedCallCount)) {
            $this->_fail('expected ' . $this->_expectedCallCount . ' calls, but more appeared');
        }
        $this->_isCalled = true;
        $this->_callCounter++;
    }

    /**
     * Проверка совпадения аргументов с ожидаемыми
     *
     * @param array $args
     */
    private function _checkArgs(array $args)
    {
        if ($this->_expectedArgsList !== null) {
            if (empty($this->_expectedArgsList)) {
                $this->_fail('expect args list ended');
            }
            $expectedArgs = array_shift($this->_expectedArgsList);
        } else {
            $expectedArgs = $this->_expectedArgs;
        }

        if ($expectedArgs !== null) {
            if ($expectedArgs === false) {
                $expectedArgs = [];
                $message = 'expected no args, but they appeared';
            } else {
                $message = 'unexpected args';
            }

            $this->_assertEquals($expectedArgs, $args, $message);
        } elseif ($this->_expectedArgsSubset !== null) {
            $args = array_intersect_key($args, $this->_expectedArgsSubset);
            $this->_assertEquals($this->_expectedArgsSubset, $args, 'unexpected args subset');
        }
    }

    /**
     * Получить возвращаемое значение для мока
     *
     * @param array $args
     * @param mixed $origMethodResult
     * @return mixed|void
     */
    private function _getReturnValue(array $args, $origMethodResult)
    {
        if (!$this->_isReturnDataSet) {
            return;
        }

        if ($this->_returnValue !== null) {
            return $this->_returnValue;
        } elseif ($this->_returnAction !== null) {
            $action = $this->_returnAction;
            if ($this->_sniffMode) {
                return $action($args, $origMethodResult, $this->_additionalVar);
            } else {
                return $action($args, $this->_additionalVar);
            }
        } elseif ($this->_exceptionConf !== null) {
            $exceptionClass = $this->_exceptionConf['class'];
            throw new $exceptionClass($this->_exceptionConf['message']);
        } elseif ($this->_returnValueList !== null) {
            if (empty($this->_returnValueList)) {
                $this->_fail('return value list ended');
            }
            return array_shift($this->_returnValueList);
        } else {
            return null;
        }
    }

    /**
     * Определяем имя переименованного метода
     *
     * @return string
     */
    public function getOriginalMethodName(): string
    {
        return self::RENAME_PREFIX . $this->_method;
    }

    /**
     * Кол-во вызовов данного мока
     *
     * @return int
     */
    public function getCallCount(): int
    {
        return $this->_callCounter;
    }

    /**
     * Деструктор
     */
    public function __destruct()
    {
        $this->restore();
    }

    /**
     * Проверка на вызов, возвращаем оригинальный метод
     *
     * @param bool $hasFailed Был ли тест завален
     * @throws \PHPUnit\Framework\AssertionFailedError|\Exception
     */
    public function restore($hasFailed = false): void
    {
        if ($this->_mockRestored) {
            return;
        }

        runkit_method_remove($this->_class, $this->_method);
        runkit_method_rename($this->_class, $this->getOriginalMethodName(), $this->_method);
        $this->_mockRestored = true;

        // если тест завален, то проверки не нужны
        // если полная подмена, то счётчик не работает
        if (!$hasFailed && !$this->_isFullMock) {
            if ($this->_expectedCallCount == self::EXPECT_CALL_ONCE) {
                $this->_assertEquals(true, $this->_isCalled, 'is not called!');
            } else {
                $this->_assertEquals($this->_expectedCallCount, $this->getCallCount(), 'unexpected call count');
            }
        }
    }

    /**
     * восстановлен ли мок
     *
     * @return bool
     */
    public function isRestored(): bool
    {
        return $this->_mockRestored;
    }

    /**
     * Мокаем оригинальный метод
     *
     * @throws \PHPUnit\Framework\AssertionFailedError|\Exception
     */
    private function _mockOriginalMethod(): void
    {
        $reflectionMethod = new ReflectionMethod($this->_class, $this->_method);

        $flags = $this->_getRunkitFlags($reflectionMethod);
        $mockerClass = MethodMocker::class;
        // можно было делать не через строки, а через функции
        // но в таком случае ранкит глючит при наследовании
        if ($this->_sniffMode) {
            $origMethodCall = ($flags & RUNKIT_ACC_STATIC ? 'self::' : '$this->') . $this->getOriginalMethodName();
            $mockAction = '$result = ' . $origMethodCall . '(...func_get_args()); ' . $mockerClass . "::doAction('" . $this->_id . "'" . ', func_get_args(), $result); return $result;';
        } else {
            if ($this->_isFullMock) {
                $mockAction = $this->_action;
            } else {
                $mockAction = "return " . $mockerClass . "::doAction('" . $this->_id . "', func_get_args());";
            }
        }

        // всю инфу вытаскиваем до того, как переименуем
        $docBlock = (string)$reflectionMethod->getDocComment();
        $parameters = $this->_getMethodParameters($reflectionMethod);
        $returnType = $this->_getReturnType($reflectionMethod);

        runkit_method_rename(
            $this->_class,
            $this->_method,
            $this->getOriginalMethodName()
        );

        if (is_string($mockAction)) {
            $success = runkit_method_add(
                $this->_class,
                $this->_method,
                $parameters,
                $mockAction,
                $flags,
                $docBlock,
                $returnType
            );
        } else {
            $success = runkit_method_add($this->_class, $this->_method, $mockAction, $flags);
        }
        if (!$success) {
            $this->_fail("can't mock method");        // @codeCoverageIgnore
        }
    }

    /**
     * Формируем сообщение об ошибке
     *
     * @param string $msg
     * @return string
     */
    private function _getErrorMessage(string $msg): string
    {
        return $this->_class . '::' . $this->_method . ' (mocked in ' . $this->_callerFile . ' line ' . $this->_callerLine . ') - ' . $msg;
    }

    /**
     * Если мок восстановлен, то кидает ексепшн
     *
     * @throws \PHPUnit\Framework\AssertionFailedError|\Exception
     */
    private function _checkNotRestored(): void
    {
        if ($this->_mockRestored) {
            $this->_fail('mock entity is restored!');
        }
    }

    /**
     * Проверка, что такой метод можно мокнуть
     *
     * @throws \PHPUnit\Framework\AssertionFailedError|\Exception
     */
    private function _checkCanMock(): void
    {
        if (!class_exists($this->_class)) {
            $this->_fail('class "' . $this->_class . '" does not exist!');
        }

        if (!method_exists($this->_class, $this->_method)) {
            $this->_fail('method "' . $this->_method . '" in class "' . $this->_class . '" does not exist!');
        }

        $reflectionMethod = new ReflectionMethod($this->_class, $this->_method);
        if ($reflectionMethod->getDeclaringClass()->getName() !== $this->_class) {
            // если замокать отнаследованный непереопределённый метод, то можно попортить класс
            $this->_fail(
                'method ' . $this->_method . ' is declared in parent class '
                . $reflectionMethod->getDeclaringClass()->getName() . ', mock parent instead!'
            );
        }

        if (!empty($this->_action) && ($this->_action instanceof \Closure)) {
            $reflectClass = new ReflectionClass($this->_class);
            $reflectParent = $reflectClass->getParentClass();
            if (!empty($reflectParent) && $reflectParent->hasMethod($this->_method)) {
                // ранкит глючит, если мокать метод в дочернем классе через коллбек
                $this->_fail("can't mock inherited method " . $this->_method . ' as Closure');
            }
        }

        if (!is_string($this->_action) && ($this->_action !== null) && !($this->_action instanceof \Closure)) {
            $this->_fail('action must be a string, a Closure or a null');
        }
    }

    /**
     * Завалить тест
     *
     * @param string $message
     */
    private function _fail(string $message): void
    {
        MethodMocker::fail($this->_getErrorMessage($message));
    }

    /**
     * Сравнить
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     */
    private function _assertEquals($expected, $actual, string $message): void
    {
        MethodMocker::assertEquals($expected, $actual, $this->_getErrorMessage($message));
    }
}
