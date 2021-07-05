<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\Shell;

use ArtSkills\Lib\Shell;
use ArtSkills\Lib\Git;
use ArtSkills\Shell\GitBranchTrimShell;
use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\Mock\MethodMocker;
use Cake\Core\Configure;
use Cake\I18n\Time;

class GitBranchTrimShellTest extends AppTestCase
{
    /**
     * Тест того, что вызывалось при запуске
     * @group Git
     */
    public function test(): void
    {
        MethodMocker::mock(GitBranchTrimShell::class, '_canDeleteRemote')
            ->willReturnValue(true);

        $git = Git::getInstance();

        $branchBefore = $git->getCurrentBranchName();

        $history = [];
        $this->_mockExecute($history);

        Configure::write(GitBranchTrimShell::CONFIGURATION_NAME, [
            'dir' => __DIR__,
        ]);

        MethodMocker::mock(\Cake\Console\Shell::class, 'out');

        $shell = new GitBranchTrimShell();
        $shell->main();

        self::assertEquals($branchBefore, $git->getCurrentBranchName(), 'Ветка не вернулась обратно');

        $deleteDateFrom = Time::parse(GitBranchTrimShell::DEFAULT_CONFIGURATION['branchDeleteInterval'])
            ->format('Y-m-d');
        $skipBranches = [Git::BRANCH_NAME_MASTER, Git::BRANCH_NAME_HEAD, $branchBefore];

        $actualHistory = $history;
        $expectedHistory = [];
        if ($branchBefore !== Git::BRANCH_NAME_MASTER) {
            $expectedHistory[] = 'git branch -a 2>&1';
            $expectedHistory[] = 'git checkout master 2>&1';
        }
        $expectedHistory[] = 'git remote update --prune 2>&1';
        $expectedHistory[] = 'git pull 2>&1';
        foreach ([Git::BRANCH_TYPE_REMOTE, Git::BRANCH_TYPE_LOCAL] as $type) {
            $expectedHistory = array_merge($expectedHistory, $this->_getCommandListMerged($type));
            $mergedBranches = $git->getMergedBranches($type);
            foreach ($mergedBranches as $branchName => $lastCommitDate) {
                if (($lastCommitDate <= $deleteDateFrom) && !in_array($branchName, $skipBranches)) {
                    $expectedHistory = array_merge($expectedHistory, $this->_getCommandListDelete($branchName, $type));
                }
            }
        }
        if ($branchBefore !== Git::BRANCH_NAME_MASTER) {
            $expectedHistory[] = 'git branch -a 2>&1';
            $expectedHistory[] = 'git checkout ' . $branchBefore . ' 2>&1';
        }

        self::assertEquals($expectedHistory, $actualHistory, 'Неправильный набор комманд');
    }

    /**
     * Мокаем _execute в Git
     *
     * @param string[] $history
     * @throws \Exception
     */
    private function _mockExecute(array &$history): void
    {
        MethodMocker::mock(Shell::class, '_exec')
            ->willReturnAction(function ($args) use (&$history) {
                $history[] = $args[0];
                if (preg_match('/^git (branch( -[ar])?|for-each-ref.*)/', $args[0])) {
                    exec($args[0], $output, $returnCode);
                    return [$returnCode === 0, $output];
                } else {
                    return [true, []];
                }
            });
    }

    /**
     * Список команд, использованных для получения списка веток
     *
     * @param string $type
     * @return string[]|bool
     */
    private function _getCommandListMerged(string $type)
    {
        $list = [];
        if ($type == Git::BRANCH_TYPE_REMOTE) {
            $list[] = 'git for-each-ref --format="%(refname) %(authordate:short)" refs/remotes/origin --merged 2>&1';
        } elseif ($type == Git::BRANCH_TYPE_LOCAL) {
            $list[] = 'git for-each-ref --format="%(refname) %(authordate:short)" refs/heads --merged 2>&1';
        } else {
            return false;
        }
        return $list;
    }

    /**
     * Список команд, использованных для удаления ветки
     *
     * @param string $branchDelete
     * @param string $type
     * @return string[]|bool
     */
    private function _getCommandListDelete(string $branchDelete, string $type)
    {
        $list = $this->_getCommandListMerged($type);
        if (empty($list)) {
            return false;
        }
        if ($type == Git::BRANCH_TYPE_REMOTE) {
            $list[] = 'git push origin --delete ' . $branchDelete . ' 2>&1';
        } elseif ($type == Git::BRANCH_TYPE_LOCAL) {
            $list[] = 'git branch ' . $branchDelete . ' -d 2>&1';
        } else {
            return false;
        }
        return $list;
    }
}
