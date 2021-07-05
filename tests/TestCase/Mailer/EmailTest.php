<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\Mailer;

use ArtSkills\Mailer\Email;
use ArtSkills\Mailer\Transport\TestEmailTransport;
use ArtSkills\TestSuite\AppTestCase;

class EmailTest extends AppTestCase
{
    /**
     * Тест на замену домена в адресе почты
     * И работу перма-мокалки
     */
    public function testChangeRecipient(): void
    {
        $text = 'test email text';
        $email = new Email();
        $email->setFrom('test@artskills.ru')
            ->setTo('test@artskills.ru', 'test-name')
            ->addTo('test@yandex.ru', 'yandex-test')
            ->addTo('info@фэшнстрит.рф', 'artskills')
            ->send($text);
        $emailData = TestEmailTransport::getMessages();
        $expectedData = [
            'to' => [
                'test@artskills.ru' => 'test-name',
                'test@yandex.ru' => 'yandex-test',
                'info@xn--h1ajjdfci6a7b.xn--p1ai' => 'artskills',
            ],
            'subject' => '',
            'template' => '',
            'layout' => 'default',
            'vars' => [],
            'message' => [$text, '', ''],
            'cc' => [],
            'bcc' => [],
            'from' => ['test@artskills.ru' => 'test@artskills.ru'],
        ];
        self::assertEquals([$expectedData], $emailData, 'Не изменился адрес почты');
    }

    /**
     * Отправка нескольким получателям
     *
     * @see Email::setToWithDelimiter()
     */
    public function testSetToWithDelimiter(): void
    {
        $email = new Email();
        $email->setToWithDelimiter('tune@nxt.ru tune2@nxt.ru, tune3@nxt.ru; tune4@nxt.ru  ');
        $email->send('123');

        $result = TestEmailTransport::getMessages();
        self::assertEquals([
            'tune@nxt.ru' => 'tune@nxt.ru',
            'tune2@nxt.ru' => 'tune2@nxt.ru',
            'tune3@nxt.ru' => 'tune3@nxt.ru',
            'tune4@nxt.ru' => 'tune4@nxt.ru',
        ], $result[0]['to']);
    }
}
