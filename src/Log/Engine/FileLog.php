<?php
namespace ArtSkills\Log\Engine;

use Cake\Error\Debugger;

class FileLog extends \Cake\Log\Engine\FileLog
{
	/** @inheritdoc */
	public function log($level, $message, array $context = []) {
		if (!empty($context[SentryLog::KEY_ADD_INFO])) {
			$message .= "\n" . Debugger::exportVar($context[SentryLog::KEY_ADD_INFO], 3);
		}
		return parent::log($level, $message, $context);
	}
}
