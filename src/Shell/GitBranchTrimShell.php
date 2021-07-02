<?php
declare(strict_types=1);

namespace ArtSkills\Shell;

use ArtSkills\Error\InternalException;
use ArtSkills\Lib\Env;
use ArtSkills\Lib\Git;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\I18n\Time;
use Exception;

/**
 * Чистилка старых (смерженных с мастером) веток.
 * Запускается из шелла посредством наследования данного класса.
 * Конфигурация:
 *    в app.php прописываем следующее:
 *    ```
 *    'Git' => [
 *        'dir' => 'Корневая попка с Git',
 *        'branchDeleteInterval' => '-7 days', // через сколько удалять смерженную с мастером ветку
 *    ],
 *    ```
 */
class GitBranchTrimShell extends Shell
{
    public const CONFIGURATION_NAME = 'Git';
    public const DEFAULT_CONFIGURATION = [
        'dir' => '',
        'branchDeleteInterval' => '-7 days',
    ];

    /**
     * Из какой директории запускать
     *
     * @var string
     */
    protected string $_fromDir = '';

    /**
     * Возраст удаляемых веток
     *
     * @var string
     */
    protected string $_branchDeleteInterval = '-7 days';

    /**
     * Чистка старых веток
     *
     * @return void
     * @throws Exception
     */
    public function main()
    {
        $this->out("Start: " . date('Y-m-d H:i:s'));

        $log = $this->_run();
        $this->out(implode("\n", $log));

        $this->out("Finish!");
    }

    /**
     * Удаление старых неиспользуемых веток
     *
     * @return string[]
     * @throws Exception
     */
    private function _run(): array
    {
        $gitConfig = Configure::read(static::CONFIGURATION_NAME);
        if (empty($gitConfig)) {
            throw new InternalException('Не сконфигурирован Git');
        }

        $gitConfig += static::DEFAULT_CONFIGURATION;

        if (empty($gitConfig['dir']) || !is_dir($gitConfig['dir'])) {
            throw new InternalException('Не сконфигурирована Git рабочая папка');
        }

        $this->_fromDir = $gitConfig['dir'];
        $this->_branchDeleteInterval = $gitConfig['branchDeleteInterval'];

        $currentDir = getcwd();
        $fromDir = $this->_fromDir();
        if (!empty($fromDir)) {
            // возможность запускать из любого места
            // чтобы можно было работать с гитом, нужно переключиться в правильную папку
            chdir($fromDir);
        }

        $git = $this->_git();
        $currentBranch = $git->getCurrentBranchName();
        if (empty($currentBranch)) {
            throw new InternalException('Гит не инициализирован');
        }

        if ($currentBranch != Git::BRANCH_NAME_MASTER) {
            if (!$git->checkout(Git::BRANCH_NAME_MASTER)) {
                throw new InternalException('Не удалось переключиться на мастера');
            }
        }

        $git->updateRefs();
        $toDeleteTypes = [Git::BRANCH_TYPE_LOCAL];
        if ($this->_canDeleteRemote()) {
            array_unshift($toDeleteTypes, Git::BRANCH_TYPE_REMOTE);
        }

        $log = [];
        foreach ($toDeleteTypes as $type) {
            $log = array_merge($log, $this->_deleteMergedOldBranches($type, [$currentBranch]));
        }

        if ($currentBranch != Git::BRANCH_NAME_MASTER) {
            $git->checkout($currentBranch);
        }

        if (!empty($fromDir)) {
            chdir($currentDir);
        }

        return $log;
    }

    /**
     * Где можно удалять удалённые(не локальные) ветки
     *
     * @return bool
     */
    protected function _canDeleteRemote(): bool
    {
        return Env::isProduction();
    }

    /**
     * Объект гита
     *
     * @return Git
     */
    protected function _git(): Git
    {
        return Git::getInstance();
    }

    /**
     * Из какой директории запускать
     *
     * @return string
     */
    protected function _fromDir(): string
    {
        if (Env::isLocal() || Env::isUnitTest()) {
            return '';
        }

        return $this->_fromDir;
    }

    /**
     * подчищает старые неиспользуемые ветки
     *
     * @param string $type
     * @param string[] $skipBranches
     * @return string[] log
     */
    protected function _deleteMergedOldBranches(string $type, array $skipBranches = []): array
    {
        $git = $this->_git();
        $skipBranches = array_merge($skipBranches, [Git::BRANCH_NAME_MASTER, Git::BRANCH_NAME_HEAD]);
        $mergedBranches = $git->getMergedBranches($type);
        $deleteDateFrom = Time::parse($this->_branchDeleteInterval)->toDateString();
        $log = [];
        foreach ($mergedBranches as $branchName => $lastCommitDate) {
            if ($lastCommitDate > $deleteDateFrom) {
                continue;
            }
            if (in_array($branchName, $skipBranches)) {
                $log[] = 'Skipped ' . $type . ' branch ' . $branchName . ' (is current or master)!';
            } else {
                $git->deleteBranch($branchName, $type);
                $log[] = 'Deleted old merged ' . $type . ' branch ' . $branchName . ' (last commit date: ' . $lastCommitDate . ')!';
            }
        }
        return $log;
    }
}
