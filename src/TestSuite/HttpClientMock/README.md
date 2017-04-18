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
## Аараметр `$method`:
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
    * `singleCall()` - один раз
    * `anyCall()` - как минимум 1 раз (по-умолчанию)
    * `expectCall(int <кол-во>)` - если мы специально хотим указать, что вызовов быть не должно, то `$mock->expectCall(0);`
* Проверка тела запроса (POST данных) - `expectBody(array|string $body)`. Порядок следования аргументов не учитывается. Если нужно отловить запрос с JSON POST, то просто передаём строку, закодированную в json_encode.
* Возвращаемое значение (понятное дело, что в конченом счете все конвертируется в строку):
    * `willReturnString(string <Строка>)`
    * `willReturnJson(array <JSON массив>)`
    * `willReturnFile(string <Путь к файлу>)`
    * `willReturnAction(callable <функция>)` На вход функция получает объект `Cake\Network\Http\Request`, на выходе должная быть возвращена строка
    * Если ничего не указать, ты получим `\Exception`
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
