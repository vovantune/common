<?php

namespace ArtSkills\Log\Engine;

use Cake\Error\Debugger;

/**
 * Работает в паре с [Sentry](https://github.com/ArtSkills/common/tree/master/src/Log/Engine), записывает дополнительно в лог SentryLog::KEY_ADD_INFO:
 * ```php
 * Log::error('Неожиданный ответ какого-нибудь api', [
 *    SentryLog::KEY_ADD_INFO => [
 *       'request' => 'request data',
 *       'response' => 'response data',
 *    ],
 * ]);
 * ```
 */
class FileLog extends \Cake\Log\Engine\FileLog
{
    /**
     * @inheritdoc
     * @phpstan-ignore-next-line
     */
    public function log($level, $message, array $context = [])
    {
        if (!empty($context[SentryLog::KEY_ADD_INFO])) {
            $message .= "\n" . Debugger::exportVar($context[SentryLog::KEY_ADD_INFO], 3);
        }
        return parent::log($level, $message, $context);
    }
}
