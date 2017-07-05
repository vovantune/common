# Что тут сделано
* Переопределены `ErrorHandler`, `ConsoleErrorHandler` и `ErrorHandlerMiddleware` для правильной отсылки сообщений в Sentry
* Определены наши собственные исключения для стандартной обработки ошибок в контроллерах

# Наши исключения
* [UserException](UserException.php) - для ошибок, которые нужно показывать пользователю: например, ошибок валидации. По умолчанию не логируются. Пользователь получает ответ 200 с сообщением об ошибке.
* [InternalException](InternalException.php) - для внутренних ошибок, о которых пользователю говорить не надо. По умолчанию логируется. Пользователь получает ответ 500 с сообщением "Произошла внутренняя ошибка".

## Использование исключений
Общие методы:
* `setWriteToLog(bool $writeToLog): $this` - Логировать ли исключение при поимке или нет. Методы `setLogScope(), setLogAddInfo(), setLogContext(), setAlert()` автоматически включают логирование, если оно было выключено;
* `setLogScope(string|string[] $scope): $this` - Задать, в какой scope нужно логировать;
* `setLogAddInfo(mixed $info): $this` - Задать доп. инфу ([SentryLog::KEY_ADD_INFO](../Log/Engine/README.md)) для логирования;
* `setLogContext(array $context, bool $fullOverwrite = false): $this` - Задать целиком параметр `$context` из `Log::Write()` (scope и add_info - самые используемые ключи массива `$context`, поэтому вынесены отдельно). `$fullOverwrite` определяет, перезаписать ли старое значение новым или смержить их; 
* `setAlert(bool $alert): $this` - Задать значение параметра `$alert` метода `SentryLog::logException` ([метод описан здесь](../Log/Engine/README.md));
* `log(array|null $context = [], null|bool $alert = null): void` - Залогировать это исключение, если логирование для него включено. Параметры аналогичны тому, что было в методах `setLogContext() и setAlert()`. Если параметр `null`, то берётся значение, заданное этими методами, иначе берётся параметр;
* `static instance(string $message = '', int $code = 0, \Exception $previous = null): static` - Альтернативный способ создания, более удобный для цепочки вызовов;

У `UserException` есть 2 сообщения: 
* стандартное сообщение исключения, оно будет залогировано, если лог включён;
* сообщение для пользователя. По умолчанию оно совпадает со стандартным, но его можно изменить методом `setUserMessage(string $message): $this`;
 


