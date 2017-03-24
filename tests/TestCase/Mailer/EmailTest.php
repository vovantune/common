<?php
namespace ArtSkills\Test\TestCase\Lib\EmailTest;

use ArtSkills\Mailer\Email;
use ArtSkills\Mailer\Transport\TestTransport;
use ArtSkills\TestSuite\AppTestCase;

class EmailTest extends AppTestCase
{
	/**
	 * Тест на замену домена в адресе почты
	 * И работу перма-мокалки
	 */
	public function testChangeRecipient() {
		$text = 'test email text';
		$email = new Email();
		$email->from('test@artskills.ru')
			->to('test@artskills.ru', 'test-name')
			->addTo('test@yandex.ru', 'yandex-test')
			->addTo('other@artskills.ru', 'artskills')
			->send($text);
		$emailData = TestTransport::getMessages();
		$expectedData = [
			'to' => [
				'test@artskills-studio.ru' => 'test-name',
				'test@yandex.ru' => 'yandex-test',
				'other@artskills-studio.ru' => 'artskills',
			],
			'subject' => '',
			'template' => '',
			'layout' => 'default',
			'vars' => [],
			'message' => [$text, '', ''],
		];
		self::assertEquals([$expectedData], $emailData, 'Не изменился адрес почты');
	}

}