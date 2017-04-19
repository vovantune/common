<?php
/**
 * Created by PhpStorm.
 * User: vladimirtunikov
 * Date: 19.04.17
 * Time: 15:17
 */

namespace ArtSkills\Lib;

use ArtSkills\Traits\Singleton;
use Cake\Core\Configure;

/**
 * Библиотека для проверки совместимости CakePHP на разнные плюшки
 */
class CakeCompatibility
{
	use Singleton;

	const GETTER_SETTER_SUPPORT = '3.4.0';

	/**
	 * Текущая версия кейка
	 *
	 * @var string
	 */
	private static $_currentVersion = null;

	/**
	 * CakeCompatibility constructor.
	 */
	private function __construct() {
		static::$_currentVersion = Configure::version();
	}

	/**
	 * Получаем текущаю версия кейка
	 *
	 * @return string
	 */
	public function getVersion() {
		return static::$_currentVersion;
	}

	/**
	 * Применяются ли геттеры/сеттеры, введённые в версии 3.4
	 * @return bool
	 */
	public static function supportSetters() {
		return static::_checkForSupport('3.4');
	}


	/**
	 * Проверка на поддержку фнукционала. Подразумевается, что мы всегда сравниваем на более позднюю версию кейка.
	 *
	 * @param string $needleVersion
	 * @return boolean
	 */
	private static function _checkForSupport($needleVersion) {
		return version_compare(static::getInstance()->getVersion(), $needleVersion, '>=');
	}
}