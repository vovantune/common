<?php

namespace ArtSkills\TestSuite;

use ArtSkills\Error\InternalException;
use ArtSkills\Filesystem\Folder;
use ArtSkills\Lib\AppCache;
use ArtSkills\Mailer\Transport\TestEmailTransport;
use ArtSkills\ORM\Entity;
use ArtSkills\Lib\Env;
use ArtSkills\Lib\Misc;
use ArtSkills\Lib\Strings;
use ArtSkills\TestSuite\Mock\MethodMocker;
use ArtSkills\TestSuite\Mock\ConstantMocker;
use ArtSkills\TestSuite\HttpClientMock\HttpClientAdapter;
use ArtSkills\TestSuite\HttpClientMock\HttpClientMocker;
use ArtSkills\TestSuite\Mock\PropertyAccess;
use ArtSkills\TestSuite\PermanentMocks\MockFileLog;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Inflector;

/**
 * Тестовое окружение
 *
 * @package App\Test
 */
trait TestCaseTrait
{

	/**
	 * Набор постоянных моков
	 *
	 * @var ClassMockEntity[]
	 */
	private $_permanentMocksList = [];

	/**
	 * Отключённые постоянные моки
	 *
	 * @var array className => true
	 */
	private $_disabledMocks = [];

	/**
	 * Список правильно проинициализированных таблиц
	 *
	 * @var array
	 */
	private static $_tableRegistry = [];


	/** вызывать в реальном setUpBeforeClass */
	protected static function _setUpBeforeClass() {
		static::_clearSingletones();
	}

	/**
	 * Инициализация тестового окружения
	 */
	protected function _setUp() {
		$this->_clearCache();
		$this->_initPermanentMocks();
		$this->_loadFixtureModels();

		HttpClientAdapter::enableDebug();
		$this->_setUpLocal();
	}

	/**
	 * Чиста тестового окружения
	 */
	protected function _tearDown() {
		/** @var TestCase $this */
		ConstantMocker::restore();
		PropertyAccess::restoreStaticAll();
		Time::setTestNow(null); // сбрасываем тестовое время
		TestEmailTransport::clearMessages();
		$this->_tearDownLocal();

		try {
			MethodMocker::restore($this->hasFailed());
		} finally {
			$this->_destroyPermanentMocks();
			$this->_disabledMocks = [];

			HttpClientMocker::clean($this->hasFailed());
		}
	}

	/** для локальных действий на setUp */
	protected function _setUpLocal() {
		// noop
	}

	/** для локальных действий на tearDown */
	protected function _tearDownLocal() {
		// noop
	}


	/**
	 * Отключение постоянного мока; вызывать перед parent::setUp();
	 *
	 * @param string $mockClass
	 */
	protected function _disablePermanentMock($mockClass) {
		$this->_disabledMocks[$mockClass] = true;
	}

	/**
	 * Чистка кеша
	 */
	protected function _clearCache() {
		AppCache::flushExcept(['_cake_core_', '_cake_model_']);
	}

	/**
	 * loadModel на все таблицы фикстур
	 */
	protected function _loadFixtureModels() {
		if (empty($this->fixtures)) {
			return;
		}
		foreach ($this->fixtures as $fixtureName) {
			$modelAlias = Inflector::camelize(Strings::lastPart('.', $fixtureName));
			if (TableRegistry::exists($modelAlias)) {
				TableRegistry::remove($modelAlias);
			}
			$this->{$modelAlias} = TableRegistry::get($modelAlias, [
				'className' => $modelAlias,
				'testInit' => true,
			]);
		}
	}

	/**
	 * Подменяем методы, необходимые только в тестовом окружении
	 *
	 * @throws InternalException
	 */
	private function _initPermanentMocks() {
		$permanentMocks = [
			// folder => namespace
			__DIR__ . '/PermanentMocks' => Misc::namespaceSplit(MockFileLog::class)[0],
		];
		$projectMockFolder = Env::getMockFolder();
		if (!empty($projectMockFolder)) {
			if (Env::hasMockNamespace()) {
				$projectMockNs = Env::getMockNamespace();
			} else {
				throw new InternalException('Не задан неймспейс для классов-моков');
			}
			$permanentMocks[$projectMockFolder] = $projectMockNs;
		}
		foreach ($permanentMocks as $folder => $mockNamespace) {
			$dir = new Folder($folder);
			$files = $dir->find('.*\.php');
			foreach ($files as $mockFile) {
				$mockClass = $mockNamespace . '\\' . str_replace('.php', '', $mockFile);
				if (empty($this->_disabledMocks[$mockClass])) {
					/** @var ClassMockEntity $mockClass */
					$mockClass::init();
					$this->_permanentMocksList[] = $mockClass;
				}
			}
		}
	}

	/**
	 * Сбрасываем все перманентые моки
	 */
	private function _destroyPermanentMocks() {
		foreach ($this->_permanentMocksList as $mockClass) {
			$mockClass::destroy();
		}
		$this->_permanentMocksList = [];
	}

	/**
	 * очищаем одиночек
	 * то, что одиночки создаются 1 раз, иногда может очень мешать
	 */
	protected static function _clearSingletones() {
		$singletones = static::_getSingletones();
		foreach ($singletones as $className) {
			PropertyAccess::setStatic($className, '_instance', null);
		}
	}

	/**
	 * Список классов-одиночек, которые нужно чистить после каждого теста, переопределяется в классе-родителе:
	 * ```php
	 * protected static function _getSingletones() {
	 *     return [PCO::class];
	 * }
	 * ```
	 *
	 * @return string[]
	 */
	protected static function _getSingletones() {
		// Приходится использовать метод, ибо переопределить свойство при использовании трейта нельзя
		// А заполнить свойство не получится, ибо _clearSingletones статичен
		return [];
	}


	/**
	 * Задать тестовое время
	 * Чтоб можно было передавать строку
	 *
	 * @param Time|string|null $time
	 * @param bool $clearMicroseconds убрать из времени микросекунды (PHP7).
	 * Полезно тем, что в базу микросекунды всё равно не сохранятся
	 * @return Time
	 */
	protected function _setTestNow($time = null, $clearMicroseconds = true) {
		if (!($time instanceof Time)) {
			$time = new Time($time);
		}
		if ($clearMicroseconds) {
			$time->setTime($time->hour, $time->minute, $time->second, 0);
		}
		Time::setTestNow($time);
		return $time;
	}

	/**
	 * Проверка совпадения части массива
	 * Замена нативного assertArraySubset, который не показывает красивые диффы
	 *
	 * @param array $expected
	 * @param array $actual
	 * @param string $message
	 * @param float $delta
	 * @param int $maxDepth
	 * @param bool $canonicalize
	 * @param bool $ignoreCase
	 */
	public function assertArraySubsetEquals(
		array $expected, array $actual, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = false,
		$ignoreCase = false
	) {
		$actual = array_intersect_key($actual, $expected);
		self::assertEquals($expected, $actual, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
	}

	/**
	 * Проверка части полей сущности
	 *
	 * @param array $expectedSubset
	 * @param Entity $entity
	 * @param string $message
	 * @param float $delta
	 * @param int $maxDepth
	 */
	public function assertEntitySubset(
		array $expectedSubset, Entity $entity, $message = '', $delta = 0.0, $maxDepth = 10
	) {
		$this->assertArraySubsetEquals($expectedSubset, $entity->toArray(), $message, $delta, $maxDepth);
	}

	/**
	 * Сравнение двух сущностей
	 *
	 * @param Entity $expectedEntity
	 * @param Entity $actualEntity
	 * @param string $message
	 * @param float $delta
	 * @param int $maxDepth
	 */
	public function assertEntityEqualsEntity(
		Entity $expectedEntity, Entity $actualEntity, $message = '', $delta = 0.0, $maxDepth = 10
	) {
		self::assertEquals($expectedEntity->toArray(), $actualEntity->toArray(), $message, $delta, $maxDepth);
	}

	/**
	 * Сравнение двух сущностей
	 *
	 * @param array $expectedArray
	 * @param Entity $actualEntity
	 * @param string $message
	 * @param float $delta
	 * @param int $maxDepth
	 */
	public function assertEntityEqualsArray(
		array $expectedArray, Entity $actualEntity, $message = '', $delta = 0.0, $maxDepth = 10
	) {
		self::assertEquals($expectedArray, $actualEntity->toArray(), $message, $delta, $maxDepth);
	}

	/**
	 * Содержимое файла соответствует ожидаемой строке
	 *
	 * @param string $actualFile
	 * @param string $expectedString
	 * @param string $message
	 * @param bool $canonicalize
	 * @param bool $ignoreCase
	 */
	public function assertFileEqualsString(
		$expectedString, $actualFile, $message = '', $canonicalize = false, $ignoreCase = false
	) {
		self::assertFileExists($actualFile, $message);
		self::assertEquals(
			$expectedString,
			file_get_contents($actualFile),
			$message,
			0,
			10,
			$canonicalize,
			$ignoreCase
		);
	}
}
