<?php

namespace ArtSkills\Error;

use ArtSkills\Controller\Controller;
use ArtSkills\Lib\Arrays;
use ArtSkills\Log\Engine\SentryLog;

/**
 * @internal
 */
class Exception extends \Exception
{

    /**
     * Логировать ли это исключение
     *
     * @var bool
     */
    protected $_writeToLog = true;

    /**
     * Инфа для логов
     *
     * @var array
     */
    protected $_logContext = [];

    /**
     * Слать ли оповещения при ошибке
     *
     * @var bool
     */
    protected $_alert = true;

    /**
     * Был ли вызван метод log();
     * Для избежания рекурсии между log() и SentryLog::logException();
     * Вызов log()внутри logException() сделан,
     * чтобы при обработке новых исключений старым способом вся нужная информация всё равно логировалась
     *
     * @var bool
     */
    protected $_isLogged = false;

    /**
     * Создание эксепшна в статическом стиле
     *
     * @param string $message
     * @param int $code
     * @param \Exception $previous
     * @return static
     */
    public static function instance($message = '', $code = 0, $previous = null)
    {
        return new static($message, $code, $previous);
    }

    /**
     * Писать ли об ошибке в лог
     *
     * @param bool $writeToLog
     * @return $this
     */
    public function setWriteToLog($writeToLog)
    {
        $this->_writeToLog = (bool)$writeToLog;
        return $this;
    }

    /**
     * Задать scope для логирования ошибок
     *
     * @param string|string[]|null $scope
     * @return $this
     */
    public function setLogScope($scope)
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
    public function setLogAddInfo($info)
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
    public function setAlert($alert)
    {
        $this->_alert = (bool)$alert;
        return $this->setWriteToLog(true);
    }

    /**
     * Оповещать ли об ошибке или нет
     *
     * @return bool
     */
    public function getAlert()
    {
        return $this->_alert;
    }

    /**
     * Задать контекст для логов.
     *
     * @param array $context
     * @param bool $fullOverwrite
     * @return $this
     */
    public function setLogContext(array $context, $fullOverwrite = false)
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
     */
    public function log($context = [], $alert = null)
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
    public function isLogged()
    {
        return $this->_isLogged;
    }

    /**
     * Получить место, откуда было брошено исключение,
     *
     * @return null|array ['file' => , 'line' => ]
     */
    public function getActualThrowSpot()
    {
        $trace = $this->getTrace();
        array_unshift($trace, ['file' => $this->getFile(), 'line' => $this->getLine()]);
        $excludeFiles = [
            __FILE__,
            (new \ReflectionClass(Controller::class))->getFileName(),
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
