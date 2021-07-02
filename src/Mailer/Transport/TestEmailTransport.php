<?php
declare(strict_types=1);

namespace ArtSkills\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Email;

/**
 * Почтовый транспорт, применяемый во время юнит тестирования. Фактическая отправка не происходит.
 * Получить список писем можно следующим образом:
 * ```php
 * $sentMessages = TestEmailTransport::getMessages();
 * ```
 * @SuppressWarnings(PHPMD.MethodMix)
 * @SuppressWarnings(PHPMD.MethodProps)
 */
class TestEmailTransport extends AbstractTransport
{
	/**
	 * Посланные сообщения
	 *
	 * @var array
	 */
	private static array $_messages = []; // @phpstan-ignore-line

	/**
	 * Получить список посланных сообщений
	 *
	 * @param bool $clear
	 * @return array
	 * @SuppressWarnings(PHPMD.MethodArgs)
	 * @phpstan-ignore-next-line
	 */
	public static function getMessages(bool $clear = true): array
	{
		$return = self::$_messages;
		if ($clear) {
			self::clearMessages();
		}
		return $return;
	}

	/**
	 * Очистить список посланных сообщений
	 *
	 * @return void
	 */
    public static function clearMessages()
    {
        self::$_messages = [];
    }

	/**
	 * Send mail
	 *
	 * @param Email $email Cake Email
	 * @return array{headers: string, message: string}
	 */
	public function send(Email $email): array
	{
		self::$_messages[] = [
			'from' => $email->getFrom(),
			'to' => $email->getTo(),
			'cc' => $email->getCc(),
			'bcc' => $email->getBcc(),
			'subject' => $email->getSubject(),
			'template' => $email->viewBuilder()->getTemplate(),
			'layout' => $email->viewBuilder()->getLayout(),
			'vars' => $email->getViewVars(),
            'message' => $email->message(),
        ];

        return ['headers' => 'test', 'message' => 'test'];
    }
}
