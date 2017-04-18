# Установка
* В `bootstrap.php` под строкой `require CORE_PATH . 'config' . DS . 'bootstrap.php';` дописываем следующую: 
```php
require ROOT . DS . 'vendor' . DS . 'artskills' . DS . 'common' . DS . 'src' . DS . 'config' . DS . 'bootstrap.php';
```
* Наследуем `AppController` от [ArtSkills\Controller\Controller](src/Controller/Controller.php)
* Наследуем `AppTable` от [ArtSkills\ORM\Table](src/ORM/Table.php)

# Что тут сделано

## Основные фичи
* Куча [дополнительных инструментов тестирования](src/TestSuite).
* Построитель классов Table и Entity на основе структуры базы (перенести сюда доки).
* Логирование ошибок в [Sentry](src/Log/Engine).
* Полезные фичи ORM (классы Table, Entity, Query) (написать доки).
* [Helper для работы со скриптами и стилями](src/View/Helper/AssetHelper.md)).

## Мелочь
* [Формирование](src/config/phinx.php) конфига для phinx на основе кейковского конфига подключения
* В [контроллере](src/Controller/Controller.php) - методы для стандартных json ответов
* Правильная обработка вставки NULL значений в поля типа [JSON](src/Database/Type/JsonType.php)
* [zip/unzip](src/Filesystem/File.php)
* Очистка [папок](src/Filesystem/Folder.php) по времени создания, отложенное создание папки и ещё пара мелочей
* Незначительные изменения [Http/Client](src/Http/Client.php)
* Из окружения разработчика все [емейлы](src/Mailer/Email.php) шлются на тестовый ящик; из юнит-тестов - [сохраняются](src/Mailer/Transport/TestEmailTransport.php) с возможностью достать их и проверить; и ещё пара мелочей
* [Ограничения](src/Phinx/Db/Table.php) для миграций - для полей таблиц обязательно указывать комментарии и значения по-умолчанию (либо явно указывать, что по-умолчанию значений нет)
* Трейты для [одиночек](src/Traits/Singleton.php) и [полностью статичных классов-библиотек](src/Traits/Library.php)
* [Функции](src/config/functions.php) для удобного формирования в запросах вложенных ассоциаций и полных названий полей
* Формирование конфига [кеша](src/Lib/AppCache.php)
* Некоторые удобные функции для работы с [массивами](src/Lib/Arrays.php) и [строками](src/Lib/Strings.php)
* Удобные [чтение](src/Lib/CsvReader.php) и [запись](src/Lib/CsvWriter.php) в csv
* Немного более [удобная](src/Lib/DB.php) работа с Connection
* Определение [окружения](src/Lib/Env.php) и автодополнение для чтения из Configure
* Класс для работы с [git](src/Lib/Git.php) и [чистка](src/Lib/GitBranchTrim.php) устаревших веток
* Однострочный вызов [http](src/Lib/Http.php) запросов и получение результата в нужном формате
* [Русская граматика](src/Lib/RusGram.php): правильное склонение слов с числитильными; даты
* [Транслит](src/Lib/Translit.php)
* Построитель [Url](src/Lib/Url.php). Основная фишка - использование текущего домена по всему коду (у всех разработчиков и на продакшне текущий домен разный)
* [Объект](src/Lib/ValueObject.php) для сообщений между классами да и вообще для любых целей (использование объектов вместо ассоциативных массивов ради автодополнения)

 
 
 ## Доработать
 * Скрипт [деплоя](src/Lib/Deploy.php) - написать деплой не по живому, а из соседней папки с переключением конфига веб-сервера.

## Подумать
* Возможно вынести ещё какие-нибудь конфиги
* Можно ли как-нибудь вынести общий js код