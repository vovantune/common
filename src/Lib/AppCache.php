<?php
declare(strict_types=1);

namespace ArtSkills\Lib;

use Cake\Cache\Cache;
use Cake\Core\Configure;

class AppCache
{
    const REDIS_CLASS_NAME = 'Redis';

    /**
     * Этот кеш не чистится по умолчанию при flushExcept и при деплое
     * И в дебаг-режиме у него нормальное время жизни
     *
     * @var string[]
     */
    protected static $_excludeFlushCacheList = ['session'];

    /**
     * Время жизни кеша в дебаг-режиме
     *
     * @var string
     */
    protected static string $_debugCacheDuration = '+30 seconds';

    /**
     * Почистить весь кеш
     *
     * @return void
     */
    public static function flushAll()
    {
        static::flushExcept();
    }

    /**
     * Чистит кэш за исключением переданных конфигов
     * По умолчанию исключает static::$_excludeFlushCacheList
     *
     * @param null|string[] $skipConfigs
     * @return void
     */
    public static function flushExcept(?array $skipConfigs = null)
    {
        if ($skipConfigs === null) {
            $skipConfigs = static::$_excludeFlushCacheList;
        }
        $skipConfigs = Arrays::keysFromValues((array)$skipConfigs);
        $cacheConfigs = Cache::configured();
        foreach ($cacheConfigs as $configName) {
            if (empty($skipConfigs[$configName])) {
                Cache::clear(false, $configName);
            }
        }
    }

    /**
     * Инициализация конфига кэша для bootstrap.php
     *
     * @return array
     * @phpstan-ignore-next-line
     */
    public static function getConfig(): array
    {
        $cacheConfig = Configure::consume('Cache');
        $currentServer = Env::getServerName();
        if (Env::isUnitTest()) {
            $currentServer .= 'test';
        }
        $isDebug = Env::isNotProduction();
        $redisServer = Env::getRedisServer();
        $redisPassword = Env::getRedisPassword();
        $version = defined('CORE_VERSION') ? '_' . CORE_VERSION : '';

        foreach ($cacheConfig as $configName => &$configItem) {
            if (empty($configItem['className'])) {
                $configItem['className'] = self::REDIS_CLASS_NAME;
            }

            if ($configItem['className'] === self::REDIS_CLASS_NAME) {
                $configItem['server'] = $redisServer;
                $configItem['password'] = $redisPassword;
            }

            $configVersionPrefix = $version;
            if (in_array($configName, static::$_excludeFlushCacheList)) {
                $configVersionPrefix = '';
            } else {
                if ($isDebug) {
                    $configItem['duration'] = static::$_debugCacheDuration;
                }
            }

            $configItem['prefix'] = $currentServer . $configVersionPrefix . '_' . $configName . (!empty($configItem['prefix'])
                    ? '_' . $configItem['prefix'] : '');
        }
        return $cacheConfig;
    }
}
