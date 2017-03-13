<?php
namespace ArtSkills\Traits;

trait Singleton {

	/**
	 * Объект-одиночка
	 * @var static
	 */
	static private $_instance;

	/**
	 * Защищаем от создания через new Singleton
	 */
	private function __construct() {}

	/**
	 * Защищаем от создания через клонирование
	 */
	private function __clone() {}

	/**
	 * Защищаем от создания через unserialize
	 */
	private function __wakeup() {}

	/**
	 * Возвращает объект-одиночку
	 * @return static
	 */
	static public function getInstance() {
		if (empty(static::$_instance)) {
			static::$_instance = new static();
		}

		return static::$_instance;
	}

	/**
	 * Подчищаем инстанс, если объект уничтожили
	 */
	public function __destruct() {
		static::$_instance = null;
	}
}