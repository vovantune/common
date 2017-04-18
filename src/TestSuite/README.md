# Инструментарий unit тестирования в CakePHP

## Настройка
* В файле `tests/bootstrap.php` дописываем следующее:
```php
require_once AS_COMMON . 'config/bootstrap_test.php';
```
* Наследуем тесты котроллеров от [AppControllerTestCase](AppControllerTestCase.php), а остальных методов от [AppTestCase](AppTestCase.php)
* Прописываем в конфиг запуска [phpunit.xml](phpunit.xml)

## Доступный функционал
* Автоматическая чистка всего кеша и всех тестовых таблиц для каждого теста.
* [Фикстуры из xml файлов формата MySQL Workbench](Fixture).
* [Мок методов, констант, вызов приватных методов, правка приватных свойств](HttpClientMock).
* [Мок HTTP запросов](HttpClientMock).
* [Перманентные моки на все тесты](PermanentMocks).
* Чистка одиночек после выполнения теста: объявляем ```string[] protected static function _getSingletones()``` в тестовом кейсе.
* Установка кастомной текущей даты/времени: ```$this->_setTestNow(string|\Cake\I18n\Time $time);```.
 
# Путеводитель по assert
TODO: дописать :)