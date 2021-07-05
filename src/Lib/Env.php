<?php
declare(strict_types=1);

namespace ArtSkills\Lib;

use ArtSkills\Error\InternalException;
use Cake\Core\Configure;
use Cake\Error\PHP7ErrorException;
use Exception;
use PHPUnit\Framework\AssertionFailedError;
use Throwable;

/**
 * @method static string getServerName()
 * @method static string getServerProtocol()
 * @method static string getSentryDsn()
 * @method static string getSentryOptions()
 * @method static string getHttpClientAdapter()
 * @method static string getDebugEmail()
 * @method static string getTestServerName()
 * @method static string getBaseFixtureClass()
 * @method static string getFixtureFolder()
 * @method static string getMockFolder()
 * @method static string getMockNamespace()
 * @method static string getDownloadPath()
 * @method static string getRedisServer()
 * @method static string getRedisPassword()
 * @method static bool hasSentryDsn()
 * @method static bool hasHttpClientAdapter()
 * @method static bool hasBaseFixtureClass()
 * @method static bool hasFixtureFolder()
 * @method static bool hasMockNamespace()
 * @method static bool setHttpClientAdapter(string $className)
 * @method static bool setBaseFixtureClass(string $className)
 * @method static bool setFixtureFolder(string $path)
 * @method static bool setMockFolder(string $path)
 * @method static bool setMockNamespace(string $namespace)
 * @method static int getThreadsLimit()
 * @method static setThreadsLimit(int $limit)
 * @method static array|null getApiInfo()
 * @method static setDebug(bool $enableDebug)
 */
class Env
{
    /**
     * Обращение к конфигу по названию метода, а не параметром
     *
     * @param string $name
     * @param array<int, mixed> $arguments
     * @return mixed
     * @throws InternalException
     */
    public static function __callStatic(string $name, array $arguments = [])
    {
        $prefix = 'get';
        if (Strings::startsWith($name, $prefix)) {
            $configKey = lcfirst(Strings::replacePrefix($name, $prefix));
            return Configure::read($configKey);
        }
        $prefix = 'has';
        if (Strings::startsWith($name, $prefix)) {
            $configKey = lcfirst(Strings::replacePrefix($name, $prefix));
            return Configure::check($configKey);
        }
        $prefix = 'set';
        if (Strings::startsWith($name, $prefix)) {
            $configKey = lcfirst(Strings::replacePrefix($name, $prefix));
            return Configure::write($configKey, $arguments[0]);
        }

        throw new InternalException("Undefined method $name");
    }

    /**
     * Это продакшн?
     *
     * @return bool
     */
    public static function isProduction(): bool
    {
        return !Configure::read('debug');
    }

    /**
     * Это тестовый сервер?
     * (тот, на котором реальная база)
     *
     * @return bool
     */
    public static function isTestServer(): bool
    {
        return (self::getServerName() === self::getTestServerName());
    }

    /**
     * Это сайт для разработки?
     *
     * @return bool
     */
    public static function isDevelopment(): bool
    {
        return !self::isProduction() && !self::isTestServer() && !self::isLocal();
    }

    /**
     * Это локальная тачка?
     *
     * @return bool
     */
    public static function isLocal(): bool
    {
        return !empty($_SERVER['DEV_LOCAL']);
    }

    /**
     * Это юнит-тест?
     *
     * @return bool
     */
    public static function isUnitTest(): bool
    {
        return defined('TEST_MODE') && TEST_MODE;
    }

    /**
     * Обратная проверка, т.к. чаще всего нужна именно она
     *
     * @return bool
     */
    public static function isNotProduction(): bool
    {
        return !self::isProduction();
    }

    /**
     * Работаем из консоли или от веб-сервера?
     *
     * @return bool
     */
    public static function isCli(): bool
    {
        return (php_sapi_name() === 'cli');
    }

    /**
     * Включить режим дебага
     *
     * @return void
     */
    public static function enableDebug()
    {
        Configure::write('debug', true);
    }

    /**
     * Прокидывает PHPUnit exception'ы дальше, чтоб в тесты правильно валились
     *
     * @param Exception|PHP7ErrorException|Throwable $exception
     * @return void
     * @throws AssertionFailedError
     */
    public static function checkTestException($exception)
    {
        if ($exception instanceof AssertionFailedError) {
            // ExpectationFailedException наследуется от AssertionFailedError, достаточно одного instanceof
            throw $exception;
        }
    }
}
