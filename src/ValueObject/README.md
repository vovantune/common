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

## Автоматическое формирование JSDoc структуры из ValueObject
```php
ValueObjectDocumentation::buildJsDoc(string $absFilePath, string $dstFolder);
```
Имя структуры и имя файла строится на основе преобразования PSR4 полного имени класса в имя формата PSR0, например: ```ArtSkills\Test\TestCase\ValueObject\ValueObjectFixture```
будет именоваться ```ArtSkills_Test_TestCase_ValueObject_ValueObjectFixture```. Типы свойств берутся на основе PHPDoc директивы ```@var``` для каждого из них.

## Автоматические формирование JSON schema из ValueObject
```php
ValueObjectDocumentation::buildJsonSchema(string $absFilePath, string $dstSchemaFolder, string $schemaLocationUrl)
```
Логика аналогична ```ValueObjectDocumentation::buildJsDoc```.

### Скрипт формирования документации JSDoc
```php 
vendor/artskills/common/bin/valueObjectJsDocGenerator src_file|src_dir dst_dir
```
Его можно настроить в качестве FileWatcher для PHPStorm:
* File type: PHP
* Scope: в какой папке проекта лежат данные объекты, например, ```file[site]:src/Response//*```
* Program: путь для PHP, например, ```/usr/local/bin/php```
* Arguments: ```$ProjectFileDir$/vendor/artskills/common/bin/valueObjectJsDocGenerator $FilePath$ $ProjectFileDir$/webroot/js/TypeDef/ValueObject```

### Скрипт формирования JSON schema
```php 
vendor/artskills/common/bin/valueObjectJsonSchemaGenerator src_file|src_dir dst_dir dst_url
```
Настраивается аналогично ```valueObjectJsDocGenerator```.