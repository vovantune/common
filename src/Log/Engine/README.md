# Логирование ошибок

Добавлен Log Engine для отправки ошибок в Sentry. 

То есть в Sentry можно отсылать всё то, что пишется в обычные логи. Соответственно, туда будут отправляться все ошибки, перехваченные ErrorHandler'ами.
 
## Уровни 
Для логирования предлагается использовать 3 уровня:
* error - для логирования любых ошибок, автоматически отправляется в Sentry и посылает оповещения
* warning - для некритичных ошибок, которые можно не исправлять, но стоит о них помнить и обращать внимание. Автоматически отправляется в Sentry, но не посылает оповещений
* info - логирование любой информации, в Sentry по умолчанию не шлётся, но можно послать, поставив специальный флаг. Оповещений не шлёт
* Для логирования исключений используется `SentryLog::logException(\Exception $exception, array $context, bool $alert)`. При $alert = true логирует как ошибку и шлёт оповещение, при false - как warning и без оповещений. В обоих случаях дополнительно пишет в файловый лог

Оповещения на самом деле настраиваются в настройках проекта в самом Sentry. Здесь реализовано только разделение по уровням, чтобы там можно было настроить.

## Дополнительные параметры
При использовании Log::write (или Log::error и прочих методов) в параметр $message передаётся только заголовок ошибки. Дополнительная информация (какие-то конкретные значения) передаётся в параметре $context с ключом SentryLog::KEY_ADD_INFO.
Пример:
 ```php
 Log::error('Неожиданный ответ какого-нибудь api', [
     SentryLog::KEY_ADD_INFO => [
         'request' => 'request data',
         'response' => 'response data',
     ],
 ]);
 ```
 
 Всё, что передаётся таким образом, приводится к строке методом Debugger::exportVar() со вложенностью 5. Сделано так из-за того, что сентри обрезает массивы по вложенности 3, а от объектов показывает только их класс, тогда как Debugger оказывает и содержимое объекта.
 
 То, что передано через этот параметр пишется и в файловый лог, тем же самым Debugger::exportVar().
 Все остальные ключи никак не влияют на файловый лог.
 
 Выделен отдельный ключ для передачи всех переменных текущей области видимости
 ```php
 Log::error('Все видимые переменные', [
      SentryLog::KEY_VARS => get_defined_vars(),
  ]);
 ```
 Хотелось бы передавать их автоматически, но изнутри никак не выцепить переменные внешней области. Так что если нужно логировать полное окружение - копируйте этот параметр.
 
 По умолчанию в Sentry записи группируются на основе трейса вызовов. То есть даже если сообщения будут разные, если в лог пишется из одной и той же строки, то они сгруппируются.
  
 Если такой поведение нежелательно, то можно добавить отпечаток (fingerprint). Тогда сообщения будут группироваться по нему.
 ```php
 // например, для разделения ошибок http запросов по хосту и типу ошибки
 Log::error('Ошибка запроса', [
    SentryLog::KEY_FINGERPRINT => [
        $hostName, $error,
    ],
 ]);
 ```
 Значение параметра - линейный массив
 
 Можно добавить теги для поиска и ещё каких-то фич Sentry
```php
Log::error('Сообщение с тегами', [
    SentryLog::KEY_TAGS => [
        'myCustomTag' => 'myCustomValue',
        'asd' => 'qwe',
    ],
]);
```
Значение - ассоциативный массив
 
 
 Чтобы Log::info отсылался в Sentry используется параметр SentryLog::KEY_SENTRY_SEND
 ```php
 Log::info('Я хочу, чтобы это попало в сентри!', [SentryLog::KEY_SENTRY_SEND => true]);
 ```
 
 Если нужно записать только в обычный лог или отправить только в сентри
 ```php
 Log::error('Только в сентри', [SentryLog::KEY_NO_FILE_LOG => true]);
 Log::error('Только в файлы', [SentryLog::KEY_IS_HANDLED => true]);
 ```
 
 Если внезапно трейс обрезается слишком сильно, то можно использовать ключ SentryLog::KEY_FULL_TRACE
