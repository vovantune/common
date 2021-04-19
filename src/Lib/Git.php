<?php

namespace ArtSkills\Lib;

use ArtSkills\Traits\Singleton;

/**
 * Работа с Git. Переключение веток, pull, удаление веток
 */
class Git
{
    // одиночка оставлен для обратной совместимости
    // todo: выпилить одиночку
    use Singleton;

    const BRANCH_NAME_MASTER = 'master';
    const BRANCH_NAME_HEAD = 'HEAD';

    const BRANCH_TYPE_REMOTE = 'remote';
    const BRANCH_TYPE_LOCAL = 'local';
    const BRANCH_TYPE_ALL = 'all';


    /**
     * Команда запуска git на сервере
     *
     * @var string
     */
    const GIT_COMMAND_SERVER = '/var/www/git.sh -i /var/www/github';

    /**
     * Команда запуска git на локальных тачках
     *
     * @var string
     */
    const GIT_COMMAND_LOCAL = 'git';

    /**
     * Текущая ветка
     *
     * @var string
     */
    private $_currentBranch = '';

    /**
     * Команда запуска git
     *
     * @var string
     */
    private $_gitCommand = '';

    /**
     * Список спулленных веток, чтоб не пуллить по нескольку раз
     *
     * @var string[]
     */
    private $_pulledBranches = [];

    /**
     * Папка с репозиторием
     *
     * @var string
     */
    private $_directory = '';

    /**
     * Выбираем, какой командой обращаться к гиту; вытаскиваем текущую ветку
     *
     * @param string $directory папка репозитория
     * возможность передать пустой параметр оставлена для обратной совместимости
     * todo: выпилить возможность использовать пустой параметр
     */
    public function __construct($directory = '')
    {
        $this->_directory = realpath($directory);
        $this->_gitCommand = $this->_chooseGitCommand();
        if (!empty($this->_gitCommand)) {
            list($success, $output) = $this->_execute('rev-parse --abbrev-ref HEAD');

            if ($success && !empty($output)) {
                $this->_currentBranch = $output[0];
            }
        }
    }

    /**
     * Выбираем, какой командой обращаться к гиту
     *
     * @return string
     */
    protected function _chooseGitCommand()
    {
        if (Env::isTestServer() || Env::isProduction()) {
            return self::GIT_COMMAND_SERVER;
        } elseif (Env::isLocal() || Env::isUnitTest()) {
            return self::GIT_COMMAND_LOCAL;
        }
        return '';
    }

    /**
     * Выполняем команду
     *
     * @param string $command
     * @return array [успех, вывод]
     */
    private function _execute($command)
    {
        return Shell::execFromDir($this->_directory, $this->_gitCommand . ' ' . $command);
    }

    /**
     * Возвращаем текущую активную git ветку
     *
     * @return string
     */
    public function getCurrentBranchName()
    {
        return $this->_currentBranch;
    }

    /**
     * Смена активной ветки
     *
     * @param string $name
     * @return bool
     */
    public function checkout($name)
    {
        if ($this->_currentBranch == $name) {
            return true;
        }
        if (empty($this->_currentBranch) || !in_array($name, $this->getBranchList(self::BRANCH_TYPE_ALL))) {
            return false;
        }
        return $this->_checkout($name);
    }

    /**
     * Выгружаем список доступных веток в git
     *
     * @param string $type локальная или удалённая
     * @return array
     */
    public function getBranchList($type)
    {
        if (empty($this->_currentBranch)) {
            return [];
        }
        $result = [];
        switch ($type) {
            case self::BRANCH_TYPE_REMOTE:
                $commandParam = ' -r';
                $branchPrefix = '(origin\/)';
                break;
            case self::BRANCH_TYPE_LOCAL:
                $commandParam = '';
                $branchPrefix = '(\s*)';
                break;
            case self::BRANCH_TYPE_ALL:
                $commandParam = ' -a';
                $branchPrefix = '(remotes\/origin\/)?';
                break;
            default:
                return [];
        }
        list($success, $branchList) = $this->_execute('branch' . $commandParam);
        if (!$success) {
            return [];
        }
        $nameRegexp = '/' . $branchPrefix . '([\d\w\-\.\[\]]+)/i';
        foreach ($branchList as $branchName) {
            if (preg_match($nameRegexp, $branchName, $matches)) {
                if (strtolower($matches[2]) != strtolower(self::BRANCH_NAME_HEAD)) {
                    $result[] = $matches[2];
                }
            }
        }
        return array_unique($result);
    }

    /**
     * Для внутреннего пользования, без проверок
     *
     * @param string $name
     * @return bool
     */
    private function _checkout($name)
    {
        if ($this->_currentBranch == $name) {
            return true;
        }
        if (Env::isProduction()) {
            return false;
        }
        $success = $this->_execute('checkout ' . $name)[0];
        if ($success) {
            $this->_currentBranch = $name;
        }
        return $success;
    }

    /**
     * Удаляет ветку
     *
     * @param string $name
     * @param string $type локальная или удалённая
     * @return bool
     */
    public function deleteBranch($name, $type)
    {
        if (empty($this->_currentBranch)
            || (($name == $this->_currentBranch) && ($type == self::BRANCH_TYPE_LOCAL))
            || in_array($name, [self::BRANCH_NAME_HEAD, self::BRANCH_NAME_MASTER])
            || empty($this->getMergedBranches($type)[$name])
        ) {
            return false;
        }
        if ($type == self::BRANCH_TYPE_REMOTE) {
            $command = 'push origin --delete ' . $name;
        } else {
            $command = 'branch ' . $name . ' -d';
        }
        return $this->_execFromMaster($command)[0];
    }

    /**
     * Возвращает список веток, смерженных с мастером, с датами последнего коммита
     *
     * @param string $type локальная или удалённая
     * @return array
     */
    public function getMergedBranches($type)
    {
        if (empty($this->_currentBranch)) {
            return [];
        }

        if ($type == self::BRANCH_TYPE_REMOTE) {
            $namePattern = 'refs/remotes/origin';
        } elseif ($type == self::BRANCH_TYPE_LOCAL) {
            $namePattern = 'refs/heads';
        } else {
            return [];
        }

        $command = 'for-each-ref --format="%(refname) %(authordate:short)" ' . $namePattern . ' --merged';
        list($success, $branchList) = $this->_execFromMaster($command);
        if (!$success || empty($branchList)) {
            return [];
        }

        $branchDates = [];
        foreach ($branchList as $branchData) {
            list($branchName, $lastCommitDate) = explode(' ', $branchData);
            $branchName = str_replace($namePattern . '/', '', $branchName);
            if (empty($branchName)) {
                continue;
            }
            $branchDates[$branchName] = $lastCommitDate;
        }
        unset($branchDates[self::BRANCH_NAME_MASTER], $branchDates[self::BRANCH_NAME_HEAD]);

        return $branchDates;
    }

    /**
     * Исполняет команду, находясь в мастере и переключает ветку обратно. Возвращает вывод команды
     *
     * @param string $command
     * @return array [bool success, output]
     */
    private function _execFromMaster($command)
    {
        $currentBranch = $this->_currentBranch;
        if (!$this->_checkout(self::BRANCH_NAME_MASTER)
            || !$this->pullCurrentBranch()[0]
        ) {
            return [false, []];
        }

        $result = $this->_execute($command);
        $this->_checkout($currentBranch);
        return $result;
    }

    /**
     * Делаем git pull для активной ветки
     *
     * @return array [bool success, output]
     */
    public function pullCurrentBranch()
    {
        $currentBranch = $this->_currentBranch;
        if (empty($currentBranch)) {
            return [false, ['git not inited']];
        }
        if (!empty($this->_pulledBranches[$currentBranch])) {
            return [true, []];
        }
        $result = $this->_execute('pull');
        if ($result[0]) {
            // 0 - success
            $this->_pulledBranches[$currentBranch] = true;
        }
        return $result;
    }

    /**
     * Обновляет список веток
     *
     * @return bool success
     */
    public function updateRefs()
    {
        if (empty($this->_currentBranch)) {
            return false;
        }
        return $this->_execute('remote update --prune')[0];
    }

    /**
     * Смена ветки и pull
     *
     * @param string $branchName
     * @return bool
     */
    public function changeCurrentBranch($branchName)
    {
        if (empty($this->_currentBranch) || !in_array($branchName, $this->getBranchList(self::BRANCH_TYPE_REMOTE))) {
            return false;
        }

        return ($this->_checkout($branchName) && $this->pullCurrentBranch()[0]);
    }

    /**
     * Вернуть нужные данные из запроса хука гитхаба
     *
     * @param array $requestData
     * @return null|array repo branch commit
     */
    public static function parseGithubRequest($requestData)
    {
        if (empty($requestData['payload'])) {
            return null;
        }
        $payload = Arrays::decode($requestData['payload']);
        $branchPrefix = 'refs/heads/';
        if (empty($payload)
            || empty($payload['repository']['name'])
            || empty($payload['ref'])
            || !Strings::startsWith($payload['ref'], $branchPrefix)
        ) {
            return null;
        }
        return [
            'repo' => $payload['repository']['name'],
            'branch' => Strings::replacePrefix($payload['ref'], $branchPrefix),
            'commit' => $payload['after'],
        ];
    }
}
