<?php
declare(strict_types=1);

namespace ArtSkills\Error;

use Cake\Error\FatalErrorException;

/**
 * Обработчик ошибок [Sentry](https://github.com/ArtSkills/common/tree/master/src/Log/Engine) для консоли.
 */
class ConsoleErrorHandler extends \Cake\Console\ConsoleErrorHandler
{
    use ErrorHandlerTrait;

    /**
     * @inheritdoc
     * Убрал отображение fatal error, т.к. это и так выводится на экран
     */
    public function handleException(\Exception $exception)
    {
        if (!($exception instanceof FatalErrorException)) {
            $this->_displayException($exception);
        }
        $this->_logException($exception);
        $code = $exception->getCode();
        $code = ($code && is_int($code)) ? $code : 1;
        $this->_stop($code);
    }
}
