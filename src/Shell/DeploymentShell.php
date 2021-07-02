<?php
declare(strict_types=1);

namespace ArtSkills\Shell;

use ArtSkills\Lib\Arrays;
use ArtSkills\Lib\Deployer;
use ArtSkills\Lib\Strings;
use ArtSkills\Log\Engine\SentryLog;
use Cake\Console\Shell;
use Cake\Utility\Inflector;
use Exception;

/**
 * @SuppressWarnings(PHPMD.MethodMix)
 */
abstract class DeploymentShell extends Shell
{
    public const TYPE_PRODUCTION = 'production';
    public const TYPE_TEST = 'test';

    private const CAKE_PATH = CAKE_BIN;

    /**
     * Запустить деплойщика в фоновом режиме
     * Нужно, например, если запрос на деплой приходит не из консоли, а от веб-сервера,
     *   и хочется, чтобы реквест не висел и не отваливался по таймауту
     *
     * @param string $type тип репозитория - продакшн, тест, ...
     * @param string $repo обновляемая репа
     * @param string $branch обновляемая ветка
     * @return void
     */
    public static function deployInBg(string $type, string $repo, string $branch)
    {
        self::_deployInBg($type, compact('repo', 'branch'));
    }

    /**
     * Деплой без проверок в фоновом режиме
     *
     * @param string $type
     * @return void
     */
    public static function deployCurrentInBg(string $type)
    {
        self::_deployInBg($type, ['current' => true]);
    }

    /** @inheritdoc */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->addSubcommands([
            'deploy' => [
                'help' => 'Обновить проект',
                'parser' => [
                    'options' => [
                        'type' => [
                            'help' => 'Какой проект обновлять',
                            'choices' => [self::TYPE_PRODUCTION, self::TYPE_TEST],
                            'default' => false,
                        ],
                        'data' => [
                            'help' => 'Информация об обновлении. JSON-строка, обязательно имеет ключи repo и branch для деплоя с проверками. Либо current для деплоя без проверок',
                            'default' => false,
                        ],
                    ],
                ],
            ],
            'rollback' => [
                'help' => 'Откатить проект',
                'parser' => [
                    'options' => [
                        'type' => [
                            'help' => 'Какой проект откатывать',
                            'choices' => [self::TYPE_PRODUCTION, self::TYPE_TEST],
                            'default' => false,
                        ],
                    ],
                ],
            ],
            'makeProjectSymlink' => [
                'help' => "Сделать из настоящего проекта симлинк\nОбратите внимание, есть ли у пользователя достаточно прав для этого",
                'parser' => [
                    'options' => [
                        'project-path' => [
                            'help' => 'Полный путь до проекта',
                        ],
                        'new-folder' => [
                            'help' => 'Название новой папки',
                        ],
                    ],
                ],
            ],


        ]);
        return $parser;
    }

    /**
     * Деплой
     *
     * @return void
     */
    public function deploy()
    {
        if (empty($this->params['type'])) {
            $this->abort('Не указан обязательный параметр type');
        }
        if (empty($this->params['data'])) {
            $this->abort('Не указан обязательный параметр data');
        }
        $data = Arrays::decode($this->params['data']);
        $deployer = $this->_getDeployer($this->params['type']);
        if (!empty($data['current'])) {
            $success = $deployer->deployCurrentBranch();
        } else {
            if (empty($data) || empty($data['repo']) || empty($data['branch'])) {
                $this->abort('Неправильно указан параметр data');
            }
            $success = $deployer->deploy($data['repo'], $data['branch']);
        }

        if ($success) {
            $this->out('Деплой успешно завершён');
        } else {
            $this->out('Деплой не выполнен');
        }
    }

    /**
     * Откат, ещё не реализован
     *
     * @return void
     */
    public function rollBack()
    {
        if (empty($this->params['type'])) {
            $this->abort('Не указан обязательный параметр type');
        }
        $this->_getDeployer($this->params['type']);
        // if !$deployer->canRollBack() then out 'can't rollback'
        // else $success = $deployer->rollBack()
        // success or fail message
    }

    /**
     * Сделать из настоящего проекта симлинк
     *
     * @return void
     */
    public function makeProjectSymlink()
    {
        if (empty($this->params['project-path']) || empty($this->params['new-folder'])) {
            $this->abort('Переданы не все обязательные параметры');
        }
        try {
            Deployer::makeProjectSymlink($this->params['project-path'], $this->params['new-folder']);
            $this->out('OK!');
        } catch (Exception $e) {
            SentryLog::logException($e);
        }
    }

    /**
     * Деплой в фоновом режиме
     *
     * @param string $type
     * @param array{repo?: string, branch?: string, current?: bool} $params
     * @return void
     */
    private static function _deployInBg(string $type, array $params)
    {
        $stringParams = escapeshellarg(json_encode($params));
        $type = escapeshellarg($type);
        $shellName = namespaceSplit(static::class)[1];
        $shellName = Inflector::underscore(Strings::replacePostfix($shellName, 'Shell'));
        \ArtSkills\Lib\Shell::execInBackground(static::CAKE_PATH . " $shellName deploy --type=$type --data=$stringParams");
    }

    /**
     * В зависимости от типа создать объект деплойщика
     *
     * @param string $type
     * @return Deployer
     * @throws Exception
     */
    protected function _getDeployer(string $type): Deployer
    {
        return Deployer::createFromConfig($type);
    }
}
