<?php
namespace ArtSkills\Lib;

use Cake\Cache\Cache;
use Cake\Core\Configure;

class AppCache
{
	const REDIS_CLASS_NAME = 'Redis';

	protected static $_excludeFlushCacheList = ['session'];
	protected static $_debugCacheDuration = '+30 seconds';

	/**
	 * Чистит кэш
	 * @param array $skipConfigs
	 */
	public static function flush($skipConfigs = []) {
		$skipConfigs = Arrays::keysFromValues($skipConfigs);
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
	 */
	public static function getConfig() {
		$cacheConfig = Configure::consume('Cache');
		$currentServer = Env::getServerName();
		if (Env::isUnitTest()) {
			$currentServer .= 'test';
		}
		$isDebug = Env::isNotProduction();
		$redisServer = Env::getRedisServer();
		$redisPassword = Env::getRedisPassword();

		foreach ($cacheConfig as $configName => &$configItem) {
			if (empty($configItem['className'])) {
				$configItem['className'] = self::REDIS_CLASS_NAME;
			}

			if ($configItem['className'] === self::REDIS_CLASS_NAME) {
				$configItem['server'] = $redisServer;
				$configItem['password'] = $redisPassword;
			}

			if ($isDebug && !in_array($configName, static::$_excludeFlushCacheList)) {
				$configItem['duration'] = static::$_debugCacheDuration;
			}

			$configItem['prefix'] = $currentServer . '_' . $configName . (!empty($configItem['prefix']) ? '_' . $configItem['prefix'] : '');
		}
		return $cacheConfig;
	}

}