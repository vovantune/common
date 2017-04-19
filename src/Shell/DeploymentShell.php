<?php
namespace ArtSkills\Shell;

use ArtSkills\Lib\Arrays;
use ArtSkills\Lib\Deployer;
use ArtSkills\Lib\Strings;
use ArtSkills\Log\Engine\SentryLog;
use Cake\Console\Shell;
use Cake\Utility\Inflector;

abstract class DeploymentShell extends Shell
{
	const TYPE_PRODUCTION = 'production';
	const TYPE_TEST = 'test';

	const CAKE_PATH = CAKE_BIN;

	/**
	 * Запустить деплойщика в фоновом режиме
	 * Нужно, например, если запрос на деплой приходит не из консоли, а от веб-сервера,
	 *   и хочется, чтобы реквест не висел и не отваливался по таймауту
	 *
	 * @param string $type тип репозитория - продакшн, тест, ...
	 * @param string $repo обновляемая репа
	 * @param string $branch обновляемая ветка
	 * @param string $commit к чему обновляемся. для замиси в лог
	 */
	public static function deployInBg($type, $repo, $branch, $commit) {
		$params = compact('repo', 'branch', 'commit');
		$stringParams = escapeshellarg(json_encode($params));
		$type = escapeshellarg($type);
		$shellName = namespaceSplit(static::class)[1];
		$shellName = Inflector::underscore(Strings::replacePostfix($shellName, 'Shell'));
		\ArtSkills\Lib\Shell::execInBackground(static::CAKE_PATH . " $shellName deploy --type=$type --data=$stringParams");
	}

	/** @inheritdoc */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->addSubcommands([
			'deploy' => [
				'help' => 'Обновить проект',
				'parser' => [
					'options' => [
						'type' => [
							'help' => 'Какой проект обновлять',
							'choices' => [self::TYPE_PRODUCTION, self::TYPE_TEST],
							'default' => false,
						],
						'data' => [
							'help' => 'Информация об обновлении. JSON-строка, обязательно имеет ключи repo и branch',
							'default' => false,
						],
					]
				],
			],
			'rollback' => [
				'help' => 'Откатить проект',
				'parser' => [
					'options' => [
						'type' => [
							'help' => 'Какой проект откатывать',
							'choices' => [self::TYPE_PRODUCTION, self::TYPE_TEST],
							'default' => false,
						],
					]
				],
			],
			'makeProjectSymlink' => [
				'help' => "Сделать из настоящего проекта симлинк\nОбратите внимание, есть ли у пользователя достаточно прав для этого",
				'parser' => [
					'options' => [
						'project-path' => [
							'help' => 'Полный путь до проекта',
						],
						'new-folder' => [
							'help' => 'Название новой папки',
						],
					]
				],
			],


		]);
		return $parser;
	}

	/**
	 * Деплой
	 */
	public function deploy() {
		if (empty($this->params['type'])) {
			$this->abort('Не указан обязательный параметр type');
		}
		if (empty($this->params['data'])) {
			$this->abort('Не указан обязательный параметр data');
		}
		$data = Arrays::decode($this->params['data']);
		if (empty($data) || empty($data['repo']) || empty($data['branch'])) {
			$this->abort('Неправильно указан параметр data');
		}
		$deployer = $this->_getDeployer($this->params['type']);
		$success = $deployer->deploy($data['repo'], $data['branch'], empty($data['commit']) ? '' : $data['commit'], $this->_getVersion());
		if ($success) {
			$this->out('Деплой успешно завершён');
		} else {
			$this->out('Деплой не выполнен');
		}
	}

	/**
	 * В зависимости от типа создать объект деплойщика
	 *
	 * @param string $type
	 * @return \ArtSkills\Lib\Deployer
	 */
	abstract protected function _getDeployer($type);

	/**
	 * Текущая версия
	 *
	 * @return int|null
	 */
	protected function _getVersion() {
		return CORE_VERSION;
	}

	/**
	 * Откат
	 * todo: сделать
	 */
	public function rollBack() {
		if (empty($this->params['type'])) {
			$this->abort('Не указан обязательный параметр type');
		}
		$deployer = $this->_getDeployer($this->params['type']);
		// if !$deployer->canRollBack() then out 'can't rollback'
		// else $success = $deployer->rollBack()
		// success or fail message
	}

	/**
	 * Сделать из настоящего проекта симлинк
	 */
	public function makeProjectSymlink() {
		if (empty($this->params['project-path']) || empty($this->params['new-folder'])) {
			$this->abort('Переданы не все обязательные параметры');
		}
		try {
			Deployer::makeProjectSymlink($this->params['project-path'], $this->params['new-folder']);
			$this->out('OK!');
		} catch (\Exception $e) {
			SentryLog::logException($e);
		}
	}

}