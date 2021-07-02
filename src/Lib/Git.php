<?php
declare(strict_types=1);

namespace ArtSkills\Lib;

use ArtSkills\Traits\Singleton;

/**
 * Работа с Git. Переключение веток, pull, удаление веток
 * @SuppressWarnings(PHPMD.MethodMix)
 */
class Git
{
    // одиночка оставлен для обратной совместимости
    use Singleton;

    public const BRANCH_NAME_MASTER = 'master';
    public const BRANCH_NAME_HEAD = 'HEAD';

    public const BRANCH_TYPE_REMOTE = 'remote';
    public const BRANCH_TYPE_LOCAL = 'local';
    public const BRANCH_TYPE_ALL = 'all';


    /**
     * Команда запуска git на сервере
     *
     * @var string
     */
    private const GIT_COMMAND_SERVER = '/var/www/git.sh -i /var/www/github';

    /**
     * Команда запуска git на локальных тачках
     *
     * @var string
     */
    private const GIT_COMMAND_LOCAL = 'git';

    /**
     * Текущая ветка
     *
     * @var string
     */
    private string $_currentBranch = '';

    /**
     * Команда запуска git
     *
     * @var string
     */
    private string $_gitCommand = '';

    /**
     * Список спулленных веток, чтоб не пуллить по нескольку раз
     *
     * @var array<string, bool>
     */
    private array $_pulledBranches = [];

    /**
     * Папка с репозиторием
     *
     * @var string
     */
    private string $_directory = '';

    /**
     * Выбираем, какой командой обращаться к гиту; вытаскиваем текущую ветку
     *
     * @param string $directory папка репозитория
     *                          возможность передать пустой параметр оставлена для обратной совместимости
     */
    public function __construct(string $directory = '')
    {
        $this->_directory = realpath($directory);
        $this->_gitCommand = $this->_chooseGitCommand();
        if (!empty($this->_gitCommand)) {
            [$success, $output] = $this->_execute('rev-parse --abbrev-ref HEAD');

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
    protected function _chooseGitCommand(): string
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
     * @return array{0: bool, 1: string[], 2: string, 3: int} [успех, вывод, результирующая команда, код возврата]
     */
    private function _execute(string $command): array
    {
        return Shell::execFromDir($this->_directory, $this->_gitCommand . ' ' . $command);
    }

    /**
     * Возвращаем текущую активную git ветку
     *
     * @return string
     */
    public function getCurrentBranchName(): string
    {
        return $this->_currentBranch;
    }

    /**
     * Смена активной ветки
     *
     * @param string $name
     * @return bool
     */
    public function checkout(string $name): bool
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
     * @return string[]
     */
    public function getBranchList(string $type): array
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
        [$success, $branchList] = $this->_execute('branch' . $commandParam);
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
    private function _checkout(string $name): bool
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
    public function deleteBranch(string $name, string $type): bool
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
     * @return array<string, string>
     */
    public function getMergedBranches(string $type): array
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
        [$success, $branchList] = $this->_execFromMaster($command);
        if (!$success || empty($branchList)) {
            return [];
        }

        $branchDates = [];
        foreach ($branchList as $branchData) {
            [$branchName, $lastCommitDate] = explode(' ', $branchData);
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
     * @return array{0: bool, 1: string[], 2: string, 3: int} [успех, вывод, результирующая команда, код возврата]
     */
    private function _execFromMaster(string $command): array
    {
        $currentBranch = $this->_currentBranch;
        if (!$this->_checkout(self::BRANCH_NAME_MASTER)
            || !$this->pullCurrentBranch()[0]
        ) {
            return [false, [], $command, 0];
        }

        $result = $this->_execute($command);
        $this->_checkout($currentBranch);
        return $result;
    }

    /**
     * Делаем git pull для активной ветки
     *
     * @return array{0: bool, 1: string[], 2?: string, 3?: int} [успех, вывод, результирующая команда, код возврата]
     */
    public function pullCurrentBranch(): array
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
    public function updateRefs(): bool
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
    public function changeCurrentBranch(string $branchName): bool
    {
        if (empty($this->_currentBranch) || !in_array($branchName, $this->getBranchList(self::BRANCH_TYPE_REMOTE))) {
            return false;
        }

        return ($this->_checkout($branchName) && $this->pullCurrentBranch()[0]);
    }

    /**
     * Вернуть нужные данные из запроса хука гитхаба
     *
     * @param array<string, mixed> $requestData
     * @return ?array{repo: string, branch: string, commit: string} repo branch commit
     */
    public static function parseGithubRequest(array $requestData): ?array
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
