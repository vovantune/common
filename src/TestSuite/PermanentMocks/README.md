# Перманентный мок
Все подобные классы описываются в папке `tests/Suite/Mock`. Инициализация описывается в статическом методе `init()` и вызывается во время вызова `setUp()` тестового кейса. 
Очистка каких-то внутренних переменных описывается в статическом метода `destroy()` и вызывается в `tearDown()` тестового кейса.

По-умолчанию всегда мокается запись лога в файл, заменяется на вывод консоль ([MockFileLog](MockFileLog.php)).

## Пример описания
```php
namespace App\Test\Suite\Mock;

use App\Lib\CrmApi;
use ArtSkills\TestSuite\Mock\MethodMocker;
use ArtSkills\TestSuite\ClassMockEntity;
use Cake\Error\Debugger;

class MockSendSms extends ClassMockEntity
{
    /**
     * @inheritdoc
     */
    public static function init() {
        MethodMocker::mock(CrmApi::class, 'sendSms', 'return ' . self::class . '::sendSms(...func_get_args());');
    }

    /**
     * мок отправки смс
     *
     * @param string $recipient
     * @param string $message
     */
    public static function sendSms($recipient, $message) {
        $trace = Debugger::trace(['start' => 2, 'depth' => 3, 'format' => 'array']);
        $file = str_replace([CAKE_CORE_INCLUDE_PATH, ROOT], '', $trace[0]['file']);
        $line = $trace[0]['line'];

        print "Send SMS to '$recipient' with message '$message' from $file($line)\n";
    }
}
```
## Отключение перманентного мока в кейсе
Для отчключения какого-то перманентного мока в ``setUp``` кейса или родителя делаем следующий вызов: 
```php 
$this->_disablePermanentMock(string $mockClass);
...
parent::setUp(); // отключаем именно до вызова родителя
``` 