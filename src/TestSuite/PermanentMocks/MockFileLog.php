<?php

namespace ArtSkills\TestSuite\PermanentMocks;

use ArtSkills\TestSuite\ClassMockEntity;
use ArtSkills\TestSuite\Mock\MethodMocker;
use Cake\Error\Debugger;
use Cake\Log\Engine\FileLog;

class MockFileLog extends ClassMockEntity
{
    /**
     * @inheritdoc
     */
    public static function init()
    {
        MethodMocker::mock(FileLog::class, 'log', 'return ' . self::class . '::log(...func_get_args());');
    }

    /**
     * Вывод ошибка вместо файла в консоль
     *
     * @param string $level
     * @param string $message
     */
    public static function log($level, $message)
    {
        $trace = Debugger::trace();
        $trace = explode("\n", $trace);
        $test = '';
        foreach ($trace as $line) {
            // последняя строчка трейса в которой есть слово тест и нет пхпюнит - это строка теста, вызвавшего запись в лог
            if (stristr($line, 'test') && !stristr($line, 'phpunit')) {
                $test = $line;
            }
        }
        $file = $trace[4];
        file_put_contents('php://stderr', "test: $test \n Write to '$level' log from $file: $message\n\n");
    }
}
