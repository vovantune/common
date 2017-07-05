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
Для html версии есть возможность настроить редиректы при ошибке. 
В json версии одновременно вывести ошибку и сделать редирект невозможно, поэтому использование редиректов выключено.
Настройка редиректов:
* `_setErrorRedirect(string|array|Response $redirect): void` - после вызова этого метода при поимке `UserException` будет сделан соответствующий редирект; 
* `_setErrorNoRedirect(void): void` - после вызова этого метода при поимке `UserException` редиректов не будет, продолжится рендеринг текущего экшна;
Методы для удобного бросания исключений
* `_throwUserError(string $message, bool $condition = true)` - при выполнении условия бросить ошибку, редирект задаётся методами, описанными выше;
* `_throwUserErrorRedirect(string $message, string|array|Response $redirect, bool $condition = true)` - при выполнении условия бросить ошибку и сделать редирект на переданный 2й параметр, не зависимо от методов выше;
* `_throwUserErrorNoRedirect(string $message, bool $condition = true)` - при выполнении условия бросить ошибку и не делать редирект, не зависимо от методов выше;

  
```php
function someAction() {
	$this->_setErrorRedirect('/controller/otherAction');
	// ... 
	$this->_throwUserError('empty params', $isEmptyParams); // редирект был задан _setErrorRedirect()
	$this->_throwUserError('bad param 1', $isBadParam1); // редирект был задан _setErrorRedirect()
	$this->_throwUserErrorRedirect('error', '/otherController/action', $someError); // редирект на 2й параметр
	$this->_throwUserErrorNoRedirect('error', $otherError); // без редиректа
	// ...
	if (
		$condition1
		|| $condition2
		|| $condition3
		|| $condition4
		|| $condition5
	) {
		// условие в if, а не как параметр 
		$this->_throwUserError('error');
	}
	// ...
	if ($somethingUnexpected) {
		$this->_throwInternalError('something unexpected happened', $infoAboutError, $logScope);
		// внутренняя ошибка, редирект не произойдёт, будет ошибка 500 и неинформативное сообщение
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
	// из-за наличия редиректов такой экшн не будет работать как .json
	// но если их убрать, то будет работать и как .json, и как .html
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
`$_jsonResponseActions` - обрабатывать каждый экшн из списка как `'.json'`, обрабатывается в `initialize()`.

Стоит помнить, что если исключение было брошено до вызова `_setIsJsonAction()`, то оно будет обрабатываться в соответствии с расширением из запроса.

