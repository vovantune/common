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
### Конфигурация
При создании объекта в конструктор нужно передать массив с конфигурацией

Что обязательно нужно определить:
* `string[] rotateDeployFolders` - список заранее созданных папок, между которыми будет переключение. Массив полных путей
* `string projectSymlink` - главный симлинк, который будет переключаться между папками из списка выше. Полный путь
* `string cakeSubPath` - если кейк лежит не в корне проекта, то указать относительный путь до него (относительно корня проекта)
* `string[] copyFileList` - список файлов, которые нужно переносить копированием (например, локальные конфиги, которых нет в репозитории). Путь относительно корня.
* `string versionFile` - файл с номером версии, номер инкрементируется при деплое. Путь относительно корня
* `int currentVersion` - сам номер версии. Если объявлена константа CORE_VERSION, то можно не указывать
* `bool autoMigrate` - явно указать, разворачивать миграции или нет. (Например, при обновлении теста этого делать не нужно)
* `string repoName` - название текущего репозитория, для дополнительных проверок ([ниже](#run) есть более подробное объяснение)

Режим деплоя в текущую папку:
* `string singleRoot` - полный путь до корня проекта. Тогда нужно не задавать опции `projectSymlink` и `rotateDeployFolders`  

Дополнительные настройки
* `bool isDeployEnv` - можно ли здесь запуститься с такой конфигурацией. По умолчанию туда записывается `Env::isProduction()`. Зачем нужно: например, из продакшна можно деплоить и себя и тест, а из теста продакшн обновлять нельзя, можно только тест (см. [примеры](#usage) ниже)
* `string[] composerDependencies` - список зависимостей, которые нужно обновить автоматически. По умолчанию там `["artskills/common"]`
* `bool composerRequireDev` - значение false означает опцию `--no-dev`
* `string[] composerOptions` - список опций при запуске композера. По умолчанию - `--optimize-autoloader`, всегда добавляется `--no-interaction`, в зависимости от предыдущей опции - `--no-dev`

Замечание:

для опций `cakeSubPath`, `versionFile` и `copyFileList` можно указать полный путь, который будет преобразован в относительный. Для этого путь должен начинаться с одной из папок `rotateDeployFolders`, `projectSymlink`, `singleRoot`.
Для удобства, например, если уже есть константы с полными путями (см. [пример](#usage) ниже).

Конфигурацию можно определять через cake Configure. Метод `Deployer::createFromConfig(string $type)` создаст сконфигурированного деплойщика. Конфигурация должна быть объявлена в cake конфиге по пути `Deploy.$type`

### Создать симлинк
Для перехода от обычной папки проекта к симлинку есть метод `Deployer::makeProjectSymlink(string $projectPath, string $newFolderName)`.
Он переименует `$projectPath` в `$newFolderName` и создаст симлинк со старого названия на новое.
`$newFolderName` - не полный путь, а только новое название папки.

### <a name="run"></a>Запуск
Класс `Deployer`, метод `bool deploy($repo, $branch)`, `@throws \Exception`. Аргументы:
* `string $repo` Название деплоящегося репозитория. Если он не совпадает с тем, который в конфиге, то деплоя не будет. (Например, деплой делается по вебхуку из гитхаба, гитхаб в том числе присылает название репозитория. Делаем проверку, тот это репозиторий или нет)
* `string $branch` Название деплоящейся ветки, для такой же проверки. Сравнивается с текущей веткой репозитория. (Например, на тесте можно переключать ветки. Если изменилась текущая ветка, её нужно обновить, если другая - то ненужно)

Метод вернёт `false`, если не прошла проверка на то, что нужно деплоиться: не совпало название репозитория или ветки, или не то окружение.
В случае ошибок (не удалось скопировать файлы / сделать pull / composer update / phinx migrate) он будет кидать исключения.

Если проверки репозитория и ветки не нужны, то есть метод `bool deployCurrentBranch()` без параметров

Возможно деплой занимает много времени, и хотелось бы выполнять его в фоновом режиме. Например, если деплой делается по вебхуку из гитхаба, а обновить нужно и продакшн и тест, запрос может отвалиться по таймауту. Это не страшно, но неприятно.

Для этого есть `DeploymentShell`. В нём уже есть всё нужное, но так как он лежит здесь, то его не получится использовать в проекте (`bin/cake` смотрит только в папку Shell текущего проекта).

Чтобы было можно его использовать, придётся отнаследовать в папке `Shell` пустой класс. `class DeploymentShell extends \ArtSkills\Shell\DeploymentShell {}` Или использовать `class_alias()`

Запуск: `DeploymentShell::deployInBg($type, $repo, $branch)`. Параметры:
* `string $type` - тип из конфига, по которому будет сделано `Deployer::createFromConfig($type)`
* `string $repo`, `string $branch` - аналогично простому запуску `deploy()`

Или `DeploymentShell::deployCurrentInBg($type)`

В том же `DeploymentShell` есть и запуск упомянутого выше `makeProjectSymlink()`: `bin/cake deployment makeProjectSymlink --project-path='' --new-folder=''`

### <a name="examples"></a>Примеры
Пусть наш продакшн может обновлять себя и тест. Себя он обновляет посредством переключения симлинка, а тест - прям в той же папке.
```php
// пусть есть симлинк '/var/www/my_project', который сейчас залинкан на '/var/www/my_project_1'
// а cake лежит в подпапке 'cake'
// тогда стандартная константа ROOT = '/var/www/my_project_1/cake'; CONFIG = ROOT . DS . 'config' . DS
// и где-то есть PROJECT_ROOT = '/var/www/my_project_1'; и VERSION_FILE = CONFIG . 'version.txt';

$cakeSubPath = Strings::replacePrefix(ROOT, PROFECT_ROOT);
$versionFile = Strings::replacePrefix(VERSION_FILE, PROFECT_ROOT);

$configProduction = [
  'repoName' => 'my-prj',
  'mainRoot' => '/var/www/my_project',
  'rotateDeployFolders' => [
	'/var/www/my_project_1',
	'/var/www/my_project_2',
  ],
  'cakeSubPath' => $cakeSubPath, 
  'copyFileList' => [
	CONFIG . 'app_local.php',
  ],
  'versionFile' => $versionFile,
  'autoMigrate' => true,                                     // на продакшне миграции запускаем
  'isDeployEnv' => Env::isProduction(),                      // продакшн можно деплоить только из продакшна
];

$configTest = [
  'repoName' => 'my-prj',
  'singleRoot' => '/var/www/my_project_test',               // обновление в эту папку, без симлинков и переключений
  'cakeSubPath' => $cakeSubPath,
  'versionFile' => $versionFile,
  'autoMigrate' => false,                                   // при обновлении теста миграции запускать не стоит
  'isDeployEnv' => Env::isProduction() || Env::isTest(),    // тест можно обновить из продакшна или из теста
];

/*
В $configProduction можно было бы написать 
[
  'cakeSubPath' => ROOT, 
  'versionFile' => VERSION_FILE, 
]
но вот в $configTest уже нельзя
ROOT = '/var/www/my_project_1/cake', и от него нужно отрезать часть '/var/www/my_project_1'
В конфигурации $configProduction '/var/www/my_project_1' есть в списке 'rotateDeployFolders', и мы можем догадаться, что нужно отрезать от ROOT
Но в $configTest такого нет, и там вычислить не получится
*/
```

Тогда:

без проверок
```php
$version = 111;

$productionDeployer = new Deployer($configProduction);
$productionDeployer->deployCurrentBranch();

$testDeployer = new Deployer($configTest);
$testDeployer->deployCurrentBranch();
```

либо
```php
// app.php:
// ...
'Deploy' => [
  'production' => $configProduction,
  'test' => $configTest,
],
// ...

Deployer::createFromConfig('production')->deployCurrentBranch();
Deployer::createFromConfig('test')->deployCurrentBranch();
```

с проверками (допустим, хук на гитхаб)
```php
$payload = Git::parseGithubRequest($_POST);

$productionDeployer = new Deployer($configProduction);
$productionDeployer->deploy($payload['repo'], $payload['branch']);

$testDeployer = new Deployer($configTest);
$testDeployer->deploy($payload['repo'], $payload['branch']);
```

в фоновом режиме
```php
// DeploymentShell.php
class DeploymentShell extends \ArtSkills\Shell\DeploymentShell {}

// other_file.php
$payload = Git::parseGithubRequest($_POST);

DeploymentShell::deployInBg(DeploymentShell::TYPE_PRODUCTION, $payload['repo'], $payload['branch']);
DeploymentShell::deployInBg(DeploymentShell::TYPE_TEST, $payload['repo'], $payload['branch']);
```



