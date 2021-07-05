<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\Controller;

use ArtSkills\Error\UserException;
use ArtSkills\Lib\Env;
use ArtSkills\Log\Engine\SentryLog;
use ArtSkills\TestSuite\AppControllerTestCase;
use ArtSkills\TestSuite\Mock\MethodMocker;
use Cake\Log\Log;
use PHPUnit\Framework\AssertionFailedError;
use ReflectionClass;
use TestApp\Controller\TestController;

class ControllerTest extends AppControllerTestCase
{

    /** @inheritdoc */
    public function tearDown()
    {
        Env::enableDebug();
        parent::tearDown();
    }

    /** Успешный JSON ответ */
    public function testJsonOk(): void
    {
        $this->get('/test/getJsonOk');
        $this->assertJsonOKEquals(['testProperty' => 123]);
    }

    /** Ответ об ошибке */
    public function testJsonError(): void
    {
        $this->get('/test/getJsonError');
        $this->assertJsonErrorEquals('Тестовая ошибка', 'Некорректное сообщение об ошибке', ['errorProperty' => 123]);
    }

    /** Пустой JSON ответ */
    public function testEmptyJson(): void
    {
        $this->get('/test/getEmptyJson');
        $this->assertJsonOKEquals([]);
    }

    /** ValueObject в качестве результата */
    public function testGetValueObject(): void
    {
        $this->get('/test/getValueObjectJson');
        $this->assertJsonOKEquals([
            'testProperty' => 'testData',
        ]);
    }

    /** ответ с ошибкой из исключения */
    public function testErrorFromException(): void
    {
        $this->get('/test/getJsonException');
        $this->assertJsonErrorEquals('test exception', 'Некорректное сообщение об ошибке', ['someData' => 'test']);
    }

    /**
     * Если было исключение phpunit
     */
    public function testUnitException(): void
    {
        $this->expectExceptionMessage("test unit exception");
        $this->expectException(AssertionFailedError::class);
        $this->get('/test/getJsonExceptionUnit');
    }

    /** Стандартная обработка ошибок, json */
    public function testStandardErrorJson(): void
    {
        MethodMocker::mock(SentryLog::class, 'logException')->expectCall(0);
        $this->get('/test/getStandardErrorJson');
        $this->assertJsonErrorEquals('test json message');
    }

    /** Стандартная обработка ошибок, json, немного сконфигурированная обработка */
    public function testStandardErrorJsonConfigured(): void
    {
        MethodMocker::mock(SentryLog::class, 'logException')
            ->singleCall()
            ->willReturnAction(function ($args) {
                /** @var UserException $exception */
                $exception = $args[0];
                self::assertEquals('log message', $exception->getMessage());
                self::assertEquals('user message', $exception->getUserMessage());
                self::assertEquals([
                    'scope' => [
                        (int)0 => 'some scope',
                    ],
                    '_addInfo' => 'some info',
                ], $args[1]);
                self::assertEquals(false, $args[2]);
            });
        $this->get('/test/getStandardErrorJsonConfigured');
        $this->assertJsonErrorEquals('user message');
    }

    /** Стандартная обработка ошибок, html, flash */
    public function testStandardErrorFlash(): void
    {
        MethodMocker::mock(SentryLog::class, 'logException')->expectCall(0);
        $this->_initFlashSniff(1);
        $this->get('/test/getStandardErrorFlash');
        $this->assertFlashError('test flash message');
        $this->assertResponseCode(200);
    }

    /** Стандартная обработка ошибок, flash, редирект */
    public function testStandardErrorRedirect(): void
    {
        MethodMocker::mock(SentryLog::class, 'logException')->expectCall(0);
        $this->_initFlashSniff(1);
        $this->get('/test/getStandardErrorRedirect');
        $this->assertFlashError('test other flash message');
        $this->assertRedirect('/test/getJsonOk');
    }

    /** Внутренняя ошибка, отдаёт 5хх; в режиме дебага есть сообщение из ексепшна */
    public function testInternalError(): void
    {
        MethodMocker::mock(SentryLog::class, 'logException')->singleCall();
        $this->_initFlashSniff(0);
        $this->get('/test/getInternalError');
        $this->assertResponseCode(500);
        $this->assertResponseContains('An Internal Error Has Occurred');
        $this->assertResponseContains('test internal error');
    }

    /** Внутренняя ошибка, отдаёт 5хх; в режиме продакшна сообщения нет */
    public function testInternalErrorProduction(): void
    {
        Env::setDebug(false); // @phpstan-ignore-line
        MethodMocker::mock(SentryLog::class, 'logException')->singleCall();
        $this->_initFlashSniff(0);
        $this->get('/test/getInternalError');
        $this->assertResponseCode(500);
        $this->assertResponseContains('An Internal Error Has Occurred');
        $this->assertResponseNotContains('test internal error');
    }


    /** Внутренняя ошибка, отдаёт json, в режиме дебага есть информация об ексепшне */
    public function testInternalErrorJson(): void
    {
        MethodMocker::mock(SentryLog::class, 'logException')->singleCall();
        $this->get('/test/getInternalErrorJson');
        $this->assertJsonErrorEquals(
            'test json message',
            'Неожиданный результат внутренней ошибки в формате json в режиме дебага',
            [
                'url' => '/test/getInternalErrorJson',
                'code' => 500,
                // в TestController вызван Controller->_throwInternalError
                // в котором вызван InternalError::instance
                // при этом file и line - из TestController
                'file' => (new ReflectionClass(TestController::class))->getFileName(),
                'line' => 147,
            ],
            500
        );
        $this->assertJsonInternalErrorEquals('test json message');
    }

    /** проверить, что у внутренних ошибок правильный трейс */
    public function testInternalErrorTrace(): void
    {
        MethodMocker::mock(SentryLog::class, 'logException')->singleCall();
        $this->get('/test/getInternalErrorJsonTrace');
        $this->assertJsonErrorEquals(
            'test trace',
            'Неожиданный результат внутренней ошибки в формате json в режиме дебага',
            [
                'url' => '/test/getInternalErrorJsonTrace',
                'code' => 500,
                // а здесь был сделан непосредственно throw new InternalError
                'file' => (new ReflectionClass(TestController::class))->getFileName(),
                'line' => 158,
            ],
            500
        );
    }

    /** Внутренняя ошибка, отдаёт json, продакшна сообщения нет */
    public function testInternalErrorJsonProduction(): void
    {
        Env::setDebug(false); // @phpstan-ignore-line
        MethodMocker::mock(SentryLog::class, 'logException')->singleCall();
        $this->get('/test/getInternalErrorJson');
        $this->assertJsonErrorEquals(
            'An Internal Error Has Occurred.',
            'Неожиданный результат внутренней ошибки в формате json в режиме продакшна',
            [
                'url' => '/test/getInternalErrorJson',
                'code' => 500,
            ],
            500
        );
        $this->assertJsonInternalErrorEquals('An Internal Error Has Occurred.');
    }

    /** Ещё раз убеждаемся, что трейса на продакшне нет */
    public function testInternalErrorJsonProductionTrace(): void
    {
        Env::setDebug(false); // @phpstan-ignore-line
        MethodMocker::mock(SentryLog::class, 'logException')->singleCall();
        $this->get('/test/getInternalErrorJsonTrace');
        $this->assertJsonErrorEquals(
            'An Internal Error Has Occurred.',
            'Неожиданный результат внутренней ошибки в формате json в режиме продакшна',
            [
                'url' => '/test/getInternalErrorJsonTrace',
                'code' => 500,
            ],
            500
        );
    }

    /** смотрим, как выбираются экшны и шаблоны */
    public function testActionAndTemplateResolve(): void
    {
        MethodMocker::mock(Log::class, 'write')->expectCall(4);

        $this->get('/cake/testName');
        $this->assertResponseOk();

        $this->get('/cake/nonExistentAction');
        $this->assertResponseError(); // 4xx
        $this->assertResponseContains('Missing Method');

        // у кейкового контроллера здесь ошибка "не найден шаблон"
        // т.е. он нашёл экшн testName, но искал шаблон testname вместо test_name
        // должна быть либо ошибка "не найден экшн", либо должен искать шаблон test_name
        $this->get('/cake/testname');
        $this->assertResponseFailure(); // 5xx
        $this->assertResponseContains('Missing Template');


        $this->get('/test/testName');
        $this->assertResponseOk();

        $this->get('/test/nonExistentAction');
        $this->assertResponseError(); // 4xx
        $this->assertResponseContains('Missing Method');

        // а у нас я исправил
        // сделал, чтобы страница открывалась
        // чтобы не было ошибок у пользователей
        $this->get('/test/testname');
        $this->assertResponseOk();

        // проверим, что ничего не испортил
        $this->get('/test/isAction');
        $this->assertResponseError(); // 4xx
        $this->assertResponseContains('Missing Method');
    }
}
