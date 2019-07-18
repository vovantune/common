ValueObject - объект, применяющийся в качестве альтернативы обмена ассоциативными массивами.

## Основаные возможности
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

# Ограничения
* У каждого свойства должен быть описан тип, причём `mixed` и `array` запрещены, только простые типы, объекты и их массивы.
* Дефолтные значения для свойств должны быть такого же типа данных.
* Каждое свойство может иметь только один тип данных.

### Скрипт формирования документации JSDoc
```php 
vendor/artskills/common/bin/valueObjectJsDocGenerator src_file|src_dir dst_dir
```
Его можно настроить в качестве FileWatcher для PHPStorm:
* File type: PHP
* Scope: в какой папке проекта лежат данные объекты, например, ```file[site]:src/Response//*```
* Program: путь для PHP, например, ```/usr/local/bin/php```
* Arguments: ```$ProjectFileDir$/vendor/artskills/common/bin/valueObjectJsDocGenerator $FilePath$ $ProjectFileDir$/webroot/js/TypeDef/ValueObject```
