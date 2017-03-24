#Константа AssetHelper::CONFIG
Константа содержит параметры загрузки CSS, JavaScript и шаблонов Handlebars.

* При явном указании путей они должны начинаться с папок assets, css или js, без слеша в начале.
* URL_PREFIX и CORE_VERSION проставятся автоматически.
* Если нужны только дефолтные скрипт и стиль, то можно ничего в конфиге не прописывать.

Структура:
```php
[
	controllerName => [ // camelCase
		actionName => [ // camelCase
			self::KEY_SCRIPT => path_to_script,     // необязательно, по умолчанию возьмётся controllerName/actionName
			self::KEY_TEMPLATE => path_to_template, // необязательно, по умолчанию возьмётся controllerName/actionName
			self::KEY_STYLE => path_to_style,       // необязательно, по умолчанию возьмётся controllerName/actionName
			self::KEY_IS_BOTTOM => false,           // необязательно, по умолчанию false, т.е. скрипт в <head>
			self::KEY_DEPEND => [
				'controllerName1.actionName1',
				'controllerName1.actionName2',
				'controllerName2.actionName3',
			],
			self::KEY_VARS => [ // переменные должны быть обязательно объявлены с проверкой типа
				'varName' => 'varType',
				'varName2' => 'varType2',
			]
		],
	],
]
```