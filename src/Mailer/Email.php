<?php
declare(strict_types=1);

namespace ArtSkills\Mailer;

use ArtSkills\Error\InternalException;
use ArtSkills\Error\UserException;
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

    const ANY_EMAIL_PATTERN = '/^[а-яa-z0-9]{1}[а-яa-z0-9\.\-\_]+@[а-яa-z0-9\.\-]+\.[а-яa-z]{2,}+$/iu';

	/**
	 * Email constructor.
	 *
	 * @param array|string|null $config Array of configs, or string to load configs from email.php
	 * @SuppressWarnings(PHPMD.MethodArgs)
	 * @phpstan-ignore-next-line
	 */
    public function __construct($config = null)
    {
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
	 * @SuppressWarnings(PHPMD.MethodArgs)
	 * @phpstan-ignore-next-line
	 */
	private function _getTestConfig($paramConfig): ?array
	{
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
    public function addListId($listId)
    {
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
	 * @param ?string $content
	 * @return bool|array
	 * @SuppressWarnings(PHPMD.MethodArgs)
	 * @phpstan-ignore-next-line
	 */
    public function send($content = null)
    {
        try {
            $result = parent::send($content);
        } catch (\Exception $e) {
            SentryLog::logException($e);
            return false;
        }
        return $result;
    }

    /**
     * Добавляем массив получателей с разделителем
     *
     * @param string $toEmails
     * @return Email
     */
    public function setToWithDelimiter(string $toEmails): self
    {
        $notifyEmails = preg_split("/[\s,;]+/", trim($toEmails));
        if (!is_array($notifyEmails)) {
            return $this;
        }

        $this->setTo(trim($notifyEmails[0]));
        // добавляем все копии

        $emailsCount = count($notifyEmails);
        if ($emailsCount > 1) {
            for ($i = 1; $i < $emailsCount; $i++) {
                $this->addTo(trim($notifyEmails[$i]));
            }
        }
        return $this;
    }

	/**
	 * переопределяем email для тестового режима
	 *
	 * @inheritdoc
	 * @param string $varName
	 * @param string|array<string|int, string> $email
	 * @throws InternalException
	 */
    protected function _setEmail($varName, $email, $name)
    {
        return parent::_setEmail($varName, $this->_getEmailList($email), $name);
    }

    /**
	 * переопределяем email для тестового режима
	 *
	 * @inheritdoc
	 * @param string $varName
	 * @param string|array<string|int, string> $email
	 * @throws InternalException
	 */
	protected function _addEmail($varName, $email, $name)
    {
        return parent::_addEmail($varName, $this->_getEmailList($email), $name);
    }

    /**
	 * Преобразуем массив email адресов
	 *
	 * @param string|array<string|int, string> $email
	 * @return array<string, string>|string
	 * @throws InternalException
	 */
	private function _getEmailList($email)
    {
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
     * @throws InternalException
     * @throws UserException
     */
    private function _getEmail(string $email): string
	{
		if (!Env::isProduction() && !Env::isUnitTest()) {
			$email = Env::getDebugEmail();
			if (empty($email)) {
				throw new InternalException('Не прописан debugEmail в конфиге!');
			}
		}
		if (!preg_match(self::ANY_EMAIL_PATTERN, $email)) {
			throw new UserException("Некорректный email: $email");
		}
		if (preg_match('/[а-я]/i', $email)) {
            [$name, $domain] = explode('@', $email);
            $email = idn_to_ascii($name, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) . '@' . idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        }

        return $email;
    }
}
