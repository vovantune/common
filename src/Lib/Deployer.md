# Деплойщик
Класс [Deployer](Deployer.php)
Перейти к [примерам](#examples)
Перейти к [описанию использования](#usage)

## Идея
Деплой подразумевает проведение нескольких операций:
* обновление репозитория (git pull)
* обновление зависимостей (composer update)
* миграции БД (phinx migrate)
* мелочёвка (чистка кеша, обновление счётчика версий)

Как минимум это должно быть автоматизировано, а ещё лучше, если дерлоить не в текущей папке.

Основные операции очень затратны: git pull и composer update обращаются к внешним данным по сети, миграции тоже могут длиться долго. Ещё ужаснее, если какая-то из операций закончится ошибкой.

## Решение
Создаётся несколько копий проекта и один симлинк. Веб-сервер и записи в кронтабе используют этот симлинк. Все операции деплоя (git pull, composer update, ...) происходят в следующей папке. При успешном завершении симлинк переключается, иначе шлётся оповещение об ошибке, и работает старая версия.

Возможность деплоить в текущую папку сохранена. Удобно для обновления тестового репозитория, например.

## Доделать
Об успешном деплое тоже можно сделать оповещение (чтоб не гадать, когда он завершился)

Откат:
* проверить, что сейчас не самая ранняя версия
* узнать предыдущую версию миграций
* откатить миграции до предыдущей версии
* очистить кеш и переключить симлинк

Можно добавить проверку, что сейчас ничего не деплоится/откатывается, чтобы нельзя было запустить одновременно
## <a name="usage"></a>Использование

Сконфигурировать нужным образом и вызывать метод `deploy()` :

### Конфигурация
При создании объекта в конструктор нужно передать массив с конфигурацией

Что обязательно нужно определить:
* `string[] rotateDeployFolders` - список заранее созданных папок, между которыми будет переключение. Массив полных путей
* `string projectSymlink` - главный симлинк, который будет переключаться между папками из списка выше. Полный путь
* `string cakeSubPath` - если кейк лежит не в корне проекта, то указать относительный путь до него (относительно корня проекта)
* `string[] copyFileList` - список файлов, которые нужно переносить копированием (например, локальные конфиги, которых нет в репозитории). Путь относительно корня.
* `string versionFile` - файл с номером версии, номер инкрементируется при деплое. Путь относительно корня
* `bool autoMigrate` - явно указать, разворачивать миграции или нет. (Например, при обновлении теста этого делать не нужно)
* `string repoName` - название текущего репозитория, для дополнительных проверок (ниже есть более подробное объяснение)

Режим деплоя в текущую папку:
* `string singleRoot` - полный путь до корня проекта, и не задавать опции `projectSymlink` и `rotateDeployFolders`  

Дополнительные настройки
* `bool isDeployEnv` - можно ли здесь запуститься с такой конфигурацией. По умолчанию туда записывается `Env::isProduction()`. Например, из продакшна можно деплоить себя и тест, а из теста - только себя, а продакшн нельзя
* `string[] composerDependencies` - список зависимостей, которые нужно обновить автоматически
* `bool composerRequireDev` - значение false означает опцию --no-dev
* `string[] composerOptions` - список опций при запуске композера. По умолчанию - --optimize-autoloader, всегда добавляется --no-interaction, в зависимости от предыдущего свойства - --no-dev

В деплойщике есть метод `makeProjectSymlink()` для быстрой и безболезненной подмены текущей папки проекта на симлинк (он переименовывает папку и делает симлинк со старого названия на новое).

Замечание:

для опций `cakeSubPath`, `versionFile` и `copyFileList` можно указать полный путь, который будет преобразован в относительный. Для этого путь должен начинаться с одной из папок `rotateDeployFolders`, `projectSymlink`, `singleRoot`.
Для удобства, например, если уже есть константы с полными путями (снизу есть пример).

### Запуск
Метод `bool deploy()`, `@throws \Exception`. Аргументы:
* `string $repo` Название деплоящегося репозитория. Для проверки того, что деплоится то, что нужно (Например, деплой делается по вебхуку из гитхаба, гитхаб в том числе присылает название репозитория. Делаем проверку, тот это репозиторий или нет)
* `string $branch` Название деплоящейся ветки. Для такой же проверки. (Например, на тесте можно переключать ветки. Если изменилась текущая ветка, её нужно обновить, если другая - то ненужно)
* `string $commit` Хеш коммита, для записи в лог, просто так
* `int $currentVersion` Текущая версия, она инкрементируется и запишется в файл.

Метод вернёт `false`, если не прошла проверка на то, что нужно деплоиться: не совпало название репозитория или ветки, или не то окружение.
В случае ошибок (не удалось скопировать файлы / сделать pull / composer update / phinx migrate) он будет кидать исключения.

Если проверки репозитория и ветки не нужны, то есть метод `bool deployCurrentBranch($currentVersion)`

Возможно деплой занимает много времени, и хотелось бы выполнять его в фоновом режиме. Например, если деплой делается по вебхуку из гитхаба, а обновить нужно и продакшн и тест, запрос может отвалиться по таймауту. Это не страшно, но неприятно.

Для этого есть `DeploymentShell`. Для использования его тоже нужно расширить, определив метод
* `\ArtSkills\Lib\Deployer _getDeployer(string $type)` - возвращает нужный объект деплойщика в зависимости от типа (про тип будет сказано ниже)

Запуск: `DeploymentShell::deployInBg()`. Параметры:
* `string $type` - тип, по которому определяется деплойщик. Определение происходит тем самым методом `_getDeployer()`
* `string $repo`, `string $branch`, `string $commit` - аналогично простому запуску `deploy()`

В том же `DeploymentShell` есть и запуск упомянутого выше `makeProjectSymlink()`

### <a name="examples"></a>Примеры
Пусть наш продакшн может обновлять себя и тест. Себя он обновляет посредством переключения симлинка, а тест - прям в той же папке.
```php
// пусть есть симлинк '/var/www/my_project', который сейчас залинкан на '/var/www/my_project_1'
// а cake лежит в подпапке 'cake'
// тогда стандартная константа ROOT = '/var/www/my_project_1/cake'; 
// CONFIG = ROOT . DS . 'config' . DS
// и где-то есть VERSION_FILE = CONFIG . 'version.txt';

$configProduction = [
  'repoName' => 'my-prj',
  'mainRoot' => '/var/www/my_project',
  'rotateDeployFolders' => [
	'/var/www/my_project_1',
	'/var/www/my_project_2',
  ],
  'cakeSubPath' => ROOT, // здесь мы можем просто взять и указать ROOT и от него отрежется '/var/www/my_project_1/', т.к. он есть в списке rotateDeployFolders 
  'copyFileList' => [
	CONFIG . 'app_local.php',
  ],
  'versionFile' => VERSION_FILE,                             // аналогично cakeSubPath, путь отрежется
  'autoMigrate' => true,                                     // на продакшне миграции запускаем
  'isDeployEnv' => Env::isProduction(),                      // продакшн можно деплоить только из продакшна
];

$configTest = [
  'repoName' => 'my-prj',
  'singleRoot' => '/var/www/my_project_test',               // обновление в эту папку, без симлинков и переключений
  'cakeSubPath' => 'cake', // а здесь указать ROOT не получится, потому что из такой конфигурации непонятно, как получить относительный путь
  'versionFile' => 'cake/version.txt',                      // аналогично, только относительный путь
  'autoMigrate' => false,                                   // при обновлении теста миграции запускать не стоит
  'isDeployEnv' => Env::isProduction() || Env::isTest(),    // тест можно обновить из продакшна или из теста
];
```

Тогда:

без проверок
```php
$version = 111;

$productionDeployer = new Deployer($configProduction);
$productionDeployer->deployCurrentBranch($version);

$testDeployer = new Deployer($configTest);
$testDeployer->deployCurrentBranch($version);
```

с проверками (допустим, хук на гитхаб)
```php
$payload = Git::parseGithubRequest($_POST);

$productionDeployer = new Deployer($configProduction);
$productionDeployer->deploy($payload['repo'], $payload['branch'], $payload['commit'], $version);

$testDeployer = new Deployer($configTest);
$testDeployer->deploy($payload['repo'], $payload['branch'], $payload['commit'], $version);
```

в фоновом режиме
```php
// DeploymentShell.php
class DeploymentShell extends \ArtSkills\Shell\DeploymentShell {
  protected function _getDeployer($type) {
    // получить $configProduction и $configTest
    // ...
    if ($type === self::TYPE_PRODUCTION) {
		return new Deployer($configProduction);
	} else {
		return new Deployer($configTest);
	}
  }
}

// other_file.php
$payload = Git::parseGithubRequest($_POST);

DeploymentShell::deployInBg(DeploymentShell::TYPE_PRODUCTION, $payload['repo'], $payload['branch'], $payload['commit']);
DeploymentShell::deployInBg(DeploymentShell::TYPE_TEST, $payload['repo'], $payload['branch'], $payload['commit']);
```



