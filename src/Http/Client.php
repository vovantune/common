<?php
namespace ArtSkills\Http;

use ArtSkills\Lib\Env;
use ArtSkills\Log\Engine\SentryLog;

class Client extends \Cake\Http\Client
{
	/**
	 * Client constructor.
	 *
	 * @param array $config
	 */
	public function __construct($config = ['redirect' => 2]) {
		// возможность глобального переопределения адаптора отправки запросов
		if (Env::hasHttpClientAdapter()) {
			$config['adapter'] = Env::getHttpClientAdapter();
		}

		parent::__construct($config);
	}

	/**
	 * @inheritdoc
	 * Обернул в try/catch для, дабы чтобы код не валилися
	 */
	protected function _doRequest($method, $url, $data, $options) {
		try {
			$result = parent::_doRequest($method, $url, $data, $options);
			return $result;
		} catch (\Exception $error) {
			SentryLog::logException($error);
			return false;
		}
	}
}