# Стандартные JSON ответы
Для отсылки JSON ответов нужно использовать один из методов:
* `sendJsonOk(array|ValueObject $data = []): null` - когда всё хорошо, отсылаются переданные данные; 
* `sendJsonError(string $message, array $data = []): null` - когда произошла какая-либо ошибка, отсылается сообщение об ошибке и переданные данные; Использовать только в случаях, когда стандартной обработкой ошибок (описано ниже) пользоваться неудобно;
* `sendJsonException(\Exception $e, array $data = []): null` - сокращение для `sendJsonError($e->getMessage())`, исключения PHPUnit прокидывает дальше, для удобства тестирования; Аналогично `sendJsonError()` стоит использовать только когда стандартной обработкой ошибок пользоваться неудобно;

Эти методы включают использование `JsonView` место обычного `View`, проставляют `_serialize`, ключи для json_encode, правильные заголовки ответа и правильно обрабатывают JSONP запросы.
 
При их использовании ответ имеет структуру:
* `"status": string 'ok'|'error'` в зависимости от того, вызывался ли `sendJsonOk()` или `sendJsonError()`;
* `"message": string` только в случае `sendJsonError()`, сообщение об ошибке
   

# Стандартная обработка ошибок
## Какие проблемы решает
Избавиться от дублирования. При обработке ошибок у нас обычно делаются одинаковые действия:
* для JSON экшнов это вызов `sendJsonError()` и `return`
* для обычных экшнов это вызов `Flash->error()` и `return null` либо `return redirect()`

Также есть проблема с пробрасыванием ошибок при вложенности. Для обработки таких ошибок мы пришли к варианту
```php
try {
	//...
	if ($error) {
		throw new \Exception('error message');
	}
	// ... 
	methodThatThrowsException();
	// ...
	return $this->sendJsonOk($data);
} catch (\Exception $e) {
	return $this->sendJsonException($e);
}
```
Такой вариант тоже даёт нам дублирование, дополнительную вложенность экшнов из-за `try`, а ещё `catch` ловит все исключения, даже те, которые пользователю отдавать не стоит (например, исключения PDO).

## Что сделано
Сделана удобная стандартная обработка ошибок. 
Скорее всего останутся случаи, в которых её использовать не удобно, тогда нужно будет обрабатывать по-старому.
Но бОльшую часть экшнов можно будет делать по-новому.

Сделано 2 типа исключений: `UserException` и `InternalException` ([подробное описание здесь](../Error/README.md)).

Если был брошен `InternalException`, то пользователю вернётся ответ с кодом 500. 
В режиме дебага будет адекватное сообщение об ошибке, а на продакшне - просто "Произошла внутренняя ошибка". 
То есть, его обработка вообще ничем не отличается от любого другого исключения.
Ошибка может быть выведена как в виде html, так и в json (подробнее в след. разделе).
Для этого был сделан местный [ErrorController](ErrorController.php), от которого нужно отнаследовать ErrorController в проекте.
А в обычном контроллере есть метод `_throwInternalError(string $message, mixed $addInfo, null|string|string[] $scope): void` для удобного кидания этих исключений.

Если был брошен `UserException`, то пользователю вернётся ответ с кодом 200 и сообщением об ошибке.
Ошибка может быть выведена как в виде html, так и в json (подробнее в след. разделе).
В случае html ошибка выведется при помощи `Flash->error()`. 
При этом если был задан редирект на случай ошибки при помощи метода `Controller->_setErrorRedirect(null|string|array|Response): void`, то произойдёт соответствующий редирект, иначе отрендерится текущий экшн.
Для удобного кидания есть метод контроллера `_throwUserError(string $message, bool|null|string|array|Response $redirect = false): void`.
Параметр `$redirect`: если это строка или массив, то их закинут в `$this->redirect()`; объект `Response` останется в неизменном виде; `null` означает не делать редирект; а `false` - использовать заданное ранее поведение.
Для ещё более удобного использования есть метод контроллера `_throwUserErrorIf(bool $condition, string $message, bool|null|string|array|Response $redirect = false): void`.
  
```php
function someAction() {
	$this->_setErrorRedirect('/controller/otherAction');
	// ... 
	$this->_throwUserErrorIf($isEmptyParams, 'empty params'); // редирект был задан _setErrorRedirect()
	$this->_throwUserErrorIf($isBadParam1, 'bad param 1'); // редирект был задан _setErrorRedirect()
	$this->_throwUserErrorIf($someError, 'error', '/otherController/action'); // редирект на 3й параметр
	// ...
	if (
		$condition1
		|| $condition2
		|| $condition3
		|| $condition4
		|| $condition5
	) {
		$this->_throwUserError('error');
	}
	// ...
	if ($somethingUnexpected) {
		$this->_throwInternalError('something unexpected happened', $infoAboutError, $logScope);
		// редирект не произойдёт, будет ошибка 500
	}
	// ...
	methodThatThrowsException();
	// ...
	// ...
	if ($someOtherError) {
		throw UserError::instance('log message')
				->setUserMessage('user message')
				->setLogScope('some_scope');
	}
	// кстати, такой экшн будет правильно работать и для запросов '.json', и для '.html'
	// хотя для '.json' вызов _setErrorRedirect() не имеет смысла, так же как и 2й параметр _throwUserError()
}
```


`UserException` обрабатываются отдельно только если они были брошены внутри экшна. 
Если они были брошены в `initialize(), beforeFilter(), beforeRender()` (думаю, такое бывает нечасто), то они обрабатываются так же, как и остальные исключения (т.е. ошибка 500).
Потому что нормально в `try` мне удалось обернуть только непосредственно вызов экшна. 
Ошибки из других мест будут отправлены в `ErrorController`, и некоторые вещи там делать не удобно.
А реализовать поведение "вывести Flash error и остаться в том же экшне" не получится вообще. 

## Тип ответа (json или html)
Тип ответа определяется расширением запроса (`'.json', '.html'` или без расширения).
При этом есть возможность всегда отдавать JSON ответ, не зависимо от расширения.
Для этого у контроллера есть метод `_setIsJsonAction(void): void` и свойство `string[] $_jsonActions`.
`_setIsJsonAction()` - обрабатывать текущий экшн как `'.json'`; 
`$_jsonActions` - обрабатывать каждый экшн из списка как `'.json'`, обрабатывается в `initialize()`.

Стоит помнить, что если исключение было брошено до вызова `_setIsJsonAction()`, то оно будет обрабатываться в соответствии с расширением из запроса.

