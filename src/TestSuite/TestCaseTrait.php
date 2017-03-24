<?php

namespace ArtSkills\TestSuite;

use ArtSkills\Filesystem\Folder;
use ArtSkills\Lib\Arrays;
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
use Cake\Datasource\ModelAwareTrait;
use Cake\I18n\Time;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Inflector;

/**
 * Тестовое окружение
 *
 * @package App\Test
 */
trait TestCaseTrait
{

	use ModelAwareTrait;
	use LocatorAwareTrait;

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
	 * Отключить постоянные дефолтные моки
	 * Для случаев, когда они будут переопределены
	 * Или просто не нужны
	 *
	 * @var string[]
	 */
	protected $_disabledDefaultMocks = [];

	/**
	 * Список классов-одиночек, которые надо чистить в тестах
	 *
	 * @var string[]
	 */
	protected static $_singletones = [];


	/** вызывать в реальном setUpBeforeClass */
	protected static function _setUpBeforeClass() {
		static::_clearSingletones();
	}

	/**
	 * Инициализация тестового окружения
	 */
	protected function _setUp() {
		$this->_clearCache();
		$this->_disabledDefaultMocks = Arrays::keysFromValues($this->_disabledDefaultMocks);
		$this->_disabledMocks += $this->_disabledDefaultMocks;
		$this->_initPermanentMocks();
		$this->_loadFixtureModels();

		HttpClientAdapter::enableDebug();
	}

	/**
	 * Чиста тестового окружения
	 */
	protected function _tearDown() {
		/** @var TestCase $this */
		MethodMocker::restore($this->hasFailed());
		ConstantMocker::restore();
		HttpClientMocker::clean($this->hasFailed());

		Time::setTestNow(null); // сбрасываем тестовое время
		$this->_destroyPermanentMocks();
		$this->_disabledMocks = [];
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
		Misc::flushCache();
	}

	/**
	 * loadModel на все таблицы фикстур
	 */
	protected function _loadFixtureModels() {
		if (empty($this->fixtures)) {
			return;
		}
		$this->modelFactory('Table', [$this->tableLocator(), 'get']);
		foreach ($this->fixtures as $fixtureName) {
			$modelAlias = Inflector::camelize(Strings::lastPart('.', $fixtureName));
			$this->loadModel($modelAlias);
		}
	}

	/**
	 * Подменяем методы, необходимые только в тестовом окружении
	 *
	 * @throws \Exception
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
				throw new \Exception('Не задан неймспейс для классов-моков');
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
		foreach (static::$_singletones as $className) {
			PropertyAccess::setStatic($className, '_instance', null);
		}
	}


	/**
	 * Задать тестовое время
	 * Чтоб можно было передавать строку
	 *
	 * @param Time|string $time
	 */
	protected function _setTestNow($time) {
		if (!($time instanceof Time)) {
			$time = new Time($time);
		}
		Time::setTestNow($time);
	}

	/**
	 * Проверка на вхождение ассоциативного массива
	 *
	 * @param array $expectedPartialArray
	 * @param array $testArray
	 * @param string $message
	 * @param float $delta
	 * @param int $maxDepth
	 */
	public function assertAssocArraySubset(
		$expectedPartialArray, $testArray, $message = '', $delta = 0.0, $maxDepth = 10
	) {
		self::assertEquals(
			$expectedPartialArray, array_intersect_key($testArray, $expectedPartialArray), $message, $delta, $maxDepth
		);
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
		$this->assertAssocArraySubset($expectedSubset, $entity->toArray(), $message, $delta, $maxDepth);
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
}
