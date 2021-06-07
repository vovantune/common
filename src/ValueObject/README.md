ValueObject - объект, применяющийся в качестве альтернативы обмена ассоциативными массивами.

## Основные возможности
* В конструкторе задаётся ассоциативным массивом заполняемые свойства. Если такого свойства не существует, то инициируется \Exception.
* Доступ к свойствам как к элементам объекта:
```php
$object = new MyObject(['second' => 16]);
$object->property = 10; // до этого было 16
```
Доступ в стиле массива ```$object['property'] = 10;``` также доступен, но он записывает ошибку в лог.
* Вариант создания без вызова new:
```php
$object = MyObject::create(array $fillValues); // MyObject extends ValueObject
```
* У каждого свойства есть setter, позволяющий делать цепочку присваиваний:
```php
$object->setProperty1('new value')->setProperty2('property 2 value'); // где имена свойств: property1 и property2
```
* Преобразование объекта в массив: ```$arr = $object->toArray();```
* Преобразование в JSON строку: ```$string = $object->toJson();``` либо ```$string = json_encode($object);```.
* Для того, чтобы свойство было класса ```Time``` или ```Date```, его имя необходимо описать в константе ```TIME_FIELDS``` или ```DATE_FIELDS``` соответственно.
В таком случае при загрузке содержимого, например, из JSON файла, строковое или числовое значение автоматически преобразуется в нужный класс.

# Ограничения
* У каждого свойства должен быть описан тип, причём `mixed` и `array` запрещены, только простые типы, объекты и их массивы.
* Дефолтные значения для свойств должны быть такого же типа данных.
* Каждое свойство может иметь только один тип данных.

# Использование в API
* Для этого необходимо отнаследовать и настроить класс [ApiDocumentationController.php](../Controller/ApiDocumentationController.php).
* Описывать классы и экшены контроллера согласно проекту https://github.com/zircote/swagger-php
* Для формирования JSDoc описания наследуется шелл [ValueObjectDocumentationShell](../Shell/ValueObjectDocumentationShell.php).
После каждого запуска данного шелла обновляется файл _webroot/js/valueObjects.js_
* Для того, чтобы тестировать описание, необходимо создать тестовый класс следующего вида:
```php
class ApiDocumentationControllerTest extends AppControllerTestCase
{
    /**
     * Тест API документации в JSON и в HTML формате. Может работать относительно долго, ибо строит апи по всему коду.
     */
    public function test()
    {
        (new ApiDocumentationTest())->testSchema($this->getJsonResponse('/apiDocumentation.json'));
    }
}
```
