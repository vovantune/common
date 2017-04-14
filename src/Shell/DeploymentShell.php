<?php
namespace App\Shell;

use ArtSkills\Lib\Arrays;
use Cake\Console\Shell;

abstract class DeploymentShell extends Shell
{
	const TYPE_PRODUCTION = 'production';
	const TYPE_TEST = 'test';

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
	 * @return int|null
	 */
	abstract protected function _getVersion();

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

}