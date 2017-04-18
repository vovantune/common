<?php
namespace ArtSkills\Error;

/**
 * Обработчик ошибок [Sentry](https://github.com/ArtSkills/common/tree/master/src/Log/Engine) от веб морды.
 */
class ErrorHandler extends \Cake\Error\ErrorHandler
{
	use ErrorHandlerTrait;
}