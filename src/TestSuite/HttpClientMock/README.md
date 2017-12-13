# Подмена HTTP запросов
Работает только в случае применения в основном коде следующих классов:
* [ArtSkills\Http\Client](../../Http/Client.php)
* [ArtSkills\Lib\Http](../../Lib/Http.php)

Все не замоканные запросы выводятся в консоль по-умолчанию, для включения/отключения данной фичи в тесте используем следующие вызовы:
```php
HttpClientAdapter::enableDebug();
HttpClientAdapter::disableDebug();
```

# Варианты подмены во время теста
```php
HttpClientMockerEntity HttpClientMocker::mock(string $url, string $method)
HttpClientMockerEntity HttpClientMocker::mockGet(string $url, array $uriArgs = [])
HttpClientMockerEntity HttpClientMocker::mockPost(string $url, array|string $expectedPostArgs = [])
```
## Параметр `$method`:
* `Cake\Http\Client\Request::METHOD_GET`
* `Cake\Http\Client\Request::METHOD_POST`
* `Cake\Http\Client\Request::METHOD_PUT`
* `Cake\Http\Client\Request::METHOD_DELETE`
* `Cake\Http\Client\Request::METHOD_PATCH`
* `Cake\Http\Client\Request::METHOD_OPTIONS`
* `Cake\Http\Client\Request::METHOD_TRACE`
* `Cake\Http\Client\Request::METHOD_HEAD`

## Доступные методы `HttpClientMockerEntity`:
* Манипуляция с кол-вом вызовов:
    * `singleCall()` - один раз;
    * `anyCall()` - как минимум 1 раз (по-умолчанию);
    * `noCalls()` - ожидается, что вызовов не будет;
    * `expectCall(int <кол-во>)` - проверка, что вызван ровно столько раз;
* Проверка тела запроса (POST данных): 
	* `expectBody(array|string $body)`. 
	Лучше передавать массив, обычные POST запросы (form-urlencoded) и JSON запросы будут раскодированы и сравниваться как массивы. 
	Но можно передать строку, и сравниваться будут строки;
	* `expectEmptyBody()`. 
	Если не был вызван expectBody(), то проверок параметров POST запроса происходить не будет.
	Но при этом, если будет замечен запрос без параметров, то будет брошено исключение, т.к. POST запрос без параметров скорее всего является ошибкой.
	Если действительно нужно сделать такой запрос, то нужно явно указать, что ожидается пустой POST при помощи этого метода;
* Задать код статуса ответа - `willReturnStatus(int $statusCode)`. По-умолчанию - 200;
* Тело ответа (понятное дело, что в конченом счете все конвертируется в строку):
    * `willReturnString(string <Строка>)`
    * `willReturnJson(array <массив, который будет сконвертмрован в JSON>)`
    * `willReturnFile(string <Путь к файлу>)`
    * `willReturnAction(callable <функция>)` На вход функция получает 2 параметра: объект `Cake\Network\Http\Request` и объект текущего мока. Мок передаётся на случай, если нужно изменить код статуса. На выходе должна быть возвращена строка.
    * Если ничего не указать, ты получим `InternalException`
* Кол-во вызовов подменённого запроса - `getCallCount()`

## Примеры
Мок с возвращаемым значением:
```php
$postData = [
    'record_id' => 1,
    'pos' => 2,
    'dsm_template' => 3,
    'dsm_settings' => 4,
    'dsm_fields' => 5,
    'item_id' => 6,
    'product_no' => 7,
];

$mock = HttpClientMocker::mock(Site::getPool() . '/crm/makeTemplate', Request::METHOD_POST)
    ->expectBody($postData)
    ->willReturnJson(['status' => 'ok']);
```

Мок с кэлбэком:
```php
public function testGenerateCalendarMagicWord() {
    $salesOrderId = 1;
    $testMagicWord = '';

    HttpClientMocker::mock(Site::getPool() . '/crm/addWordToDiscount', Request::METHOD_POST)
        ->singleCall()
        ->willReturnAction(function ($response) use (&$testMagicWord) {
            /**
             * @var Response $response
             */
            $postData = $response->body();
            $this->assertNotEmpty($postData['discount_id'], 'Не передался ID скидки');
            $this->assertNotEmpty($postData['word'], 'Не сгенерировалось волшебное слово');
            $testMagicWord = $postData['word'];
            return json_encode(['status' => 'ok']);
        });

    $resultMagicWord = Site::generateCalendarMagicWord($salesOrderId);
    $this->assertEquals($testMagicWord, $resultMagicWord, 'Вернулось некорректное магическое слово');
}
```
