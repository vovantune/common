<?php
namespace ArtSkills\Cake\Mailer\Lib;

use ArtSkills\Lib\Env;
use Cake\Log\Log;

class Email extends \Cake\Mailer\Email
{

	const ANY_EMAIL_PATTERN = '\S+\@\S+';

	/**
	 * Email constructor.
	 *
	 * @param array|string|null $config Array of configs, or string to load configs from email.php
	 */
	public function __construct($config = null) {
		parent::__construct($config);
		$this->addHeaders(['Precedence' => 'bulk']);
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
			Log::error($e->getMessage());
			return false;
		}
		return $result;
	}

	/**
	 * переопределяем email сотрудников
	 *
	 * @param string|null $email
	 * @param string|null $name
	 * @return array|Email
	 */
	public function to($email = null, $name = null) {
		if ($email === null) {
			return $this->_to;
		}
		return $this->_setEmail('_to', self::_getEmail($email), $name);
	}

	/**
	 * переопределение email сотрудников в дополнительном письме
	 *
	 * @param string $email
	 * @param null $name
	 * @return $this
	 */
	public function addTo($email, $name = null) {
		return $this->_addEmail('_to', self::_getEmail($email), $name);
	}

	/**
	 * Заменить старый artskills.ru на новый artskills-studio.ru
	 *
	 * @param string $email
	 * @return string
	 */
	public static function newAsDomain($email) {
		return str_replace('@artskills.ru', '@artskills-studio.ru', $email);
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
	private static function _getEmail($email) {
		if (!Env::isProduction() && !Env::isUnitTest()) {
			$email = Env::getDebugEmail();
			if (empty($email)) {
				throw new \Exception('Не прописан debugEmail в конфиге!');
			}
		}
		return self::newAsDomain($email);
	}
}
