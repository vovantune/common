<?php
declare(strict_types=1);

namespace ArtSkills\Error;

use ArtSkills\Controller\Controller;
use ArtSkills\Lib\Arrays;
use ArtSkills\Log\Engine\SentryLog;
use ReflectionClass;

/**
 * @internal
 * @SuppressWarnings(PHPMD.MethodMix)
 * @SuppressWarnings(PHPMD.MethodProps)
 */
class Exception extends \Exception
{

    /**
     * Логировать ли это исключение
     *
     * @var bool
     */
    protected bool $_writeToLog = true;

    /**
     * Инфа для логов
     *
     * @var array
     */
    protected array $_logContext = []; // @phpstan-ignore-line

    /**
     * Слать ли оповещения при ошибке
     *
     * @var bool
     */
    protected bool $_alert = true;

    /**
     * Был ли вызван метод log();
     * Для избежания рекурсии между log() и SentryLog::logException();
     * Вызов log()внутри logException() сделан,
     * чтобы при обработке новых исключений старым способом вся нужная информация всё равно логировалась
     *
     * @var bool
     */
    protected bool $_isLogged = false;

    /**
     * Создание эксепшна в статическом стиле
     *
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     * @return static
     */
    public static function instance(string $message = '', int $code = 0, ?\Exception $previous = null): Exception
    {
        /** @phpstan-ignore-next-line */
        return new static($message, $code, $previous);
    }

    /**
     * Писать ли об ошибке в лог
     *
     * @param bool $writeToLog
     * @return $this
     */
    public function setWriteToLog(bool $writeToLog): Exception
    {
        $this->_writeToLog = $writeToLog;
        return $this;
    }

    /**
     * Задать scope для логирования ошибок
     *
     * @param string|string[]|null $scope
     * @return $this
     */
    public function setLogScope($scope): Exception
    {
        if ($scope === null) {
            unset($this->_logContext['scope']);
            return $this;
        }
        $this->_logContext['scope'] = (array)$scope;
        return $this->setWriteToLog(true);
    }

    /**
     * Задать доп. инфу для логирования
     *
     * @param mixed $info
     * @return $this
     */
    public function setLogAddInfo($info): Exception
    {
        if ($info === null) {
            unset($this->_logContext[SentryLog::KEY_ADD_INFO]);
            return $this;
        }
        $this->_logContext[SentryLog::KEY_ADD_INFO] = $info;
        return $this->setWriteToLog(true);
    }

    /**
     * Оповещать ли об ошибке или нет
     *
     * @param bool $alert
     * @return $this
     */
    public function setAlert(bool $alert): Exception
    {
        $this->_alert = (bool)$alert;
        return $this->setWriteToLog(true);
    }

    /**
     * Оповещать ли об ошибке или нет
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getAlert(): bool
    {
        return $this->_alert;
    }

    /**
     * Задать контекст для логов.
     *
     * @param array $context
     * @param bool $fullOverwrite
     * @return $this
     * @phpstan-ignore-next-line
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public function setLogContext(array $context, bool $fullOverwrite = false): Exception
    {
        if ($fullOverwrite) {
            $this->_logContext = $context;
        } else {
            $this->_logContext = $context + $this->_logContext;
        }
        return $this->setWriteToLog(true);
    }

    /**
     * Залогировать это исключение
     *
     * @param array|null $context
     * @param null|bool $alert
     * @return void
     * @SuppressWarnings(PHPMD.MethodArgs)
     * @phpstan-ignore-next-line
     */
    public function log(?array $context = [], ?bool $alert = null)
    {
        if ($this->_writeToLog && !$this->_isLogged) {
            $contextResult = (array)$context + $this->_logContext;
            if (!empty($this->_logAddInfo)) {
                $contextResult[SentryLog::KEY_ADD_INFO] = $this->_logAddInfo;
            }
            if (!empty($this->_logScope)) {
                $contextResult['scope'] = $this->_logScope;
            }
            $this->_isLogged = true;
            SentryLog::logException($this, $contextResult, (($alert === null) ? $this->_alert : $alert));
        }
    }

    /**
     * Было ли это исключение залогировано
     *
     * @return bool
     */
    public function isLogged(): bool
    {
        return $this->_isLogged;
    }

    /**
     * Получить место, откуда было брошено исключение,
     *
     * @return ?array{file: string, line: string}
     */
    public function getActualThrowSpot(): ?array
    {
        $trace = $this->getTrace();
        array_unshift($trace, ['file' => $this->getFile(), 'line' => $this->getLine()]);
        $excludeFiles = [
            __FILE__,
            (new ReflectionClass(Controller::class))->getFileName(),
        ];
        $actualCall = null;
        foreach ($trace as $call) {
            $callFile = Arrays::get($call, 'file');
            if (!empty($callFile) && !in_array($callFile, $excludeFiles, true)) {
                $actualCall = $call;
                break;
            }
        }
        return $actualCall;
    }
}
