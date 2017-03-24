<?php

namespace ArtSkills\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Email;

class TestTransport extends AbstractTransport
{

	/**
	 * Посланные сообщения
	 *
	 * @var array
	 */
	private static $_messages = [];

	/**
	 * Получить список посланных сообщений
	 *
	 * @param bool $clear
	 * @return array
	 */
	public static function getMessages($clear = true) {
		$return = self::$_messages;
		if ($clear) {
			self::clearMessages();
		}
		return $return;
	}


	/**
	 * Очистить список посланных сообщений
	 */
	public static function clearMessages() {
		self::$_messages = [];
	}

	/**
	 * Send mail
	 *
	 * @param \Cake\Mailer\Email $email Cake Email
	 * @return array
	 */
	public function send(Email $email) {
		self::$_messages[] = [
			'to' => $email->to(),
			'subject' => $email->subject(),
			'template' => $email->viewBuilder()->template(),
			'layout' => $email->viewBuilder()->layout(),
			'vars' => $email->viewVars(),
			'message' => $email->message(),
		];
		return ['headers' => 'test', 'message' => 'test'];
	}
}
