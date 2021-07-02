<?php
declare(strict_types=1);

namespace ArtSkills\Lib;

use ArtSkills\Traits\Library;

/**
 * Запуск команд в командной строке
 * Просто обёртка над exec() с небольшими удобствами
 */
class Shell
{
    use Library;

    /**
     * Выполнить команду в консоли
     *
     * @param string|string[] $commands
     * @param bool $withErrors перенаправлять stderr в stdout
     * @param bool $stopOnFail если передан список команд, то останавливаться ли на ошибке (склеивать команды через && или ;)
     * @return array{0: bool, 1: string[], 2: string, 3: int} [успех, вывод, результирующая команда, код возврата]
     */
    public static function exec($commands, bool $withErrors = true, bool $stopOnFail = true): array
    {
        $resultCommand = self::_processCommandList($commands, $withErrors, $stopOnFail);
        return self::_exec($resultCommand);
    }

    /**
     * Запустить и не ждать выполнения
     *
     * @param string $command
     * @param string $outputRedirect
     * @return void
     */
    public static function execInBackground(string $command, $outputRedirect = '/dev/null')
    {
        exec('nohup ' . $command . ' > ' . escapeshellarg($outputRedirect) . ' 2>&1 &');
    }

    /**
     * Выполнить команду в консоли из определённого места
     *
     * @param string $directory
     * @param string|string[] $commands
     * @param bool $withErrors перенаправлять stderr в stdout
     * @param bool $stopOnFail если передан список команд, то останавливаться ли на ошибке (склеивать команды через && или ;)
     *                         Но если свалится смена директорий, то дальше не пойдёт независимо от этого параметра
     * @return array{0: bool, 1: string[], 2: string, 3: int} [успех, вывод, результирующая команда, код возврата]
     */
    public static function execFromDir(string $directory, $commands, bool $withErrors = true, bool $stopOnFail = true): array
    {
        $resultCommand = self::_processCommandList($commands, $withErrors, $stopOnFail);
        if (!empty($directory) && (getcwd() !== $directory)) {
            $cdCommand = 'cd ' . escapeshellarg($directory) . ($withErrors ? ' 2>&1' : '');
            $resultCommand = "$cdCommand && ($resultCommand)";
        }
        return self::_exec($resultCommand);
    }

    /**
     * Обёртка вызова exec для целей мока в тесте
     *
     * @param string $command
     * @return array{0: bool, 1: string[], 2: string, 3: int} [успех, вывод, результирующая команда, код возврата]
     */
    private static function _exec(string $command): array
    {
        exec($command, $output, $returnCode);
        return [$returnCode === 0, $output, $command, $returnCode];
    }

    /**
     * Взять список команд и склеить их в одну строку в зависимости от параметров
     *
     * @param string|string[] $commands
     * @param bool $withErrors
     * @param bool $stopOnFail
     * @return string
     */
    private static function _processCommandList($commands, bool $withErrors = true, bool $stopOnFail = true): string
    {
        $commands = (array)$commands;
        if ($stopOnFail) {
            $glue = ' && ';
        } else {
            $glue = ' ; ';
        }
        if ($withErrors) {
            $errorRedirect = ' 2>&1';
            $glue = $errorRedirect . $glue;
            $resultCommand = implode($glue, $commands) . $errorRedirect;
        } else {
            $resultCommand = implode($glue, $commands);
        }
        return $resultCommand;
    }
}
