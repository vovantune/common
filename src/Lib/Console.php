<?php
namespace ArtSkills\Lib;

use ArtSkills\Traits\Library;

class Console
{
	use Library;

	/**
	 * Выполнить команду в консоли
	 *
	 * @param string|string[] $commands
	 * @param bool $withErrors перенаправлять stderr в stdout
	 * @return array [успех, вывод, результирующая команда, код возврата]
	 */
	public static function exec($commands, $withErrors = true) {
		$commands = (array)$commands;
		$glue = ' ; ';
		if ($withErrors) {
			$errorRedirect = ' 2>&1';
			$glue = $errorRedirect . $glue;
			$resultCommand = implode($glue, $commands) . $errorRedirect;
		} else {
			$resultCommand = implode($glue, $commands);
		}
		exec($resultCommand, $output, $returnCode);
		return [$returnCode === 0, $output, $resultCommand, $returnCode];
	}

	/**
	 * Запустить и не ждать выполнения
	 *
	 * @param string $command
	 * @param string $outputRedirect
	 */
	public static function execInBackground($command, $outputRedirect = '/dev/null') {
		exec($command . ' > ' . escapeshellarg($outputRedirect) . ' &');
	}

	/**
	 * Выполнить команду в консоли из определённого места
	 *
	 * @param string $directory
	 * @param string $command
	 * @param bool $withErrors перенаправлять stderr в stdout
	 * @return array [успех, вывод, код возврата]
	 */
	public static function execFromDir($directory, $command, $withErrors = true) {
		if (!empty($directory) && (getcwd() !== $directory)) {
			$command = 'cd ' . escapeshellarg($directory) . ' ; ' . $command;
		}
		return self::exec($command, $withErrors);
	}

}