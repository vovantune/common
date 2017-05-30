<?php

namespace ArtSkills\Mailer;

use ArtSkills\Lib\Env;
use ArtSkills\Log\Engine\SentryLog;

/**
 * Надстройка над классом \Cake\Mailer\Email. Основные отличия:
 * * Проставляется заголовок отправки роботом.
 * * Во время теста подменяется транспорт, что позволяет получить все передаваемые письма.
 * * При включенном debug все письма отправляется на debugEmail параметр конфигурации.
 * * Если письмо не отправилось, то Exception не прокидывается.
 */
class Email extends \Cake\Mailer\Email
{

	const ANY_EMAIL_PATTERN = '\S+\@\S+';

	/**
	 * Email constructor.
	 *
	 * @param array|string|null $config Array of configs, or string to load configs from email.php
	 */
	public function __construct($config = null) {
		if (Env::isUnitTest()) {
			$config = $this->_getTestConfig($config);
		}
		parent::__construct($config);
		$this->addHeaders(['Precedence' => 'bulk']);
	}

	/**
	 * Преобразовать конфиг для тестов
	 *
	 * @param array|string|null $paramConfig
	 * @return array|null
	 */
	private function _getTestConfig($paramConfig) {
		if (is_array($paramConfig)) {
			$config = $paramConfig;
		} else {
			if (empty($paramConfig)) {
				$confKey = 'default';
			} else {
				$confKey = $paramConfig;
			}

			$config = static::getConfig($confKey);
		}
		if (is_array($config)) {
			$config['transport'] = 'test';
		} else {
			$config = $paramConfig;
		}
		return $config;
	}

	/**
	 * Добавляет заголовки
	 *
	 * @param string $listId
	 * @return $this
	 */
	public function addListId($listId) {
		$this->addHeaders([
			'List-Id' => $listId,
			'X-Postmaster-Msgtype' => $listId,
			'X-Mailru-Msgtype' => $listId,
		]);
		return $this;
	}

	/**
	 * Отправляет письмо обрабатывая исключения
	 *
	 * @param null $content
	 * @return bool|array
	 */
	public function send($content = null) {
		try {
			$result = parent::send($content);
		} catch (\Exception $e) {
			SentryLog::logException($e);
			return false;
		}
		return $result;
	}

	/**
	 * переопределяем email для тестового режима
	 *
	 * @inheritdoc
	 */
	protected function _setEmail($varName, $email, $name) {
		return parent::_setEmail($varName, $this->_getEmailList($email), $name);
	}

	/**
	 * переопределяем email для тестового режима
	 *
	 * @inheritdoc
	 */
	protected function _addEmail($varName, $email, $name) {
		return parent::_addEmail($varName, $this->_getEmailList($email), $name);
	}

	/**
	 * Преобразуем массив email адресов
	 *
	 * @param array|string $email
	 * @return array|string
	 */
	private function _getEmailList($email) {
		if (!is_array($email)) {
			return $this->_getEmail($email);
		}
		$list = [];
		foreach ($email as $key => $value) {
			if (is_int($key)) {
				$key = $value;
			}
			$key = $this->_getEmail($key);
			$list[$key] = $value;
		}
		return $list;
	}

	/**
	 * Адрес, на который слать емейл
	 * В тестовых CRM всё слать на адрес из конфига, чтоб случайно не слали клиентам
	 * В юнит-тестах не подменяется, т.к. там уже переопределён send()
	 *
	 * @param string $email
	 * @return string
	 * @throws \Exception
	 */
	private function _getEmail($email) {
		if (!Env::isProduction() && !Env::isUnitTest()) {
			$email = Env::getDebugEmail();
			if (empty($email)) {
				throw new \Exception('Не прописан debugEmail в конфиге!');
			}
		}
		return str_replace('@artskills.ru', '@artskills-studio.ru', $email);
	}
}
