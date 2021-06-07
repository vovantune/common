<?php

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
 */
class TestEmailTransport extends AbstractTransport
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
    public static function getMessages($clear = true)
    {
        $return = self::$_messages;
        if ($clear) {
            self::clearMessages();
        }
        return $return;
    }


    /**
     * Очистить список посланных сообщений
     */
    public static function clearMessages()
    {
        self::$_messages = [];
    }

    /**
     * Send mail
     *
     * @param \Cake\Mailer\Email $email Cake Email
     * @return array
     */
    public function send(Email $email)
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
