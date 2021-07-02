<?php

namespace ArtSkills\Test\TestCase\Lib;

use ArtSkills\Lib\Shell;
use ArtSkills\Lib\Git;
use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\Mock\MethodMocker;
use ArtSkills\TestSuite\Mock\MethodMockerEntity;
use ArtSkills\TestSuite\Mock\PropertyAccess;

class GitTest extends AppTestCase
{

    /**
     * Объект git
     *
     * @var ?Git
     */
    private ?Git $_git = null;

    /**
     * Команда обращения к гиту
     *
     * @var string
     */
    private string $_gitCommand = '';


    /**
     * Ветка до теста
     *
     * @var string
     */
    private string $_branchBeforeTest = '';

    /**
     * Набор exec команд
     *
     * @var string[]
     */
    private array $_executeHistory = [];

    /**
     * Текущая папка
     *
     * @var string
     */
    private string $_currentDir = '';

    /** @inheritdoc */
    public function setUp()
    {
        parent::setUp();
        $this->_git = new Git();
        $this->_gitCommand = PropertyAccess::get($this->_git, '_gitCommand');
        $this->_branchBeforeTest = $this->_git->getCurrentBranchName();
        $this->_currentDir = getcwd();
    }

    /** @inheritdoc */
    public function tearDown()
    {
        chdir($this->_currentDir);
        parent::tearDown();
    }


    /**
     * Тест функции названия текущей ветки
     */
    public function testGetCurrentBranchName(): void
    {
        self::assertNotEmpty($this->_git->getCurrentBranchName(), 'Не отобразилась ветка в тестовом режиме');
    }

    /**
     * Тест функции списка веток
     * @group Git
     */
    public function testGetBranchList(): void
    {
        $branchesLocal = $this->_git->getBranchList(Git::BRANCH_TYPE_REMOTE);
        self::assertContains('master', $branchesLocal, 'Выгрузился список веток, в котором нет ветки master');
        $branchesRemote = $this->_git->getBranchList(Git::BRANCH_TYPE_LOCAL);
        self::assertContains('master', $branchesRemote, 'Выгрузился список веток, в котором нет ветки master');
        $branchesAll = $this->_git->getBranchList(Git::BRANCH_TYPE_ALL);
        $branchesBoth = array_unique(array_merge($branchesLocal, $branchesRemote));
        sort($branchesAll);
        sort($branchesBoth);
        self::assertEquals($branchesBoth, $branchesAll, 'Список всех веток не совпадает со списками локальных и удалённых');
    }

    /**
     * Проверяет работу функции выбора смерженных веток
     */
    public function testMergedList(): void
    {
        $this->_mockExecute();
        foreach ([Git::BRANCH_TYPE_REMOTE, Git::BRANCH_TYPE_LOCAL] as $type) {
            $branchListAll = $this->_git->getBranchList($type);
            $branchListAll = array_combine($branchListAll, $branchListAll);

            $branchListMerged = $this->_git->getMergedBranches($type);

            self::assertSameSize($branchListMerged, array_intersect_key($branchListAll, $branchListMerged), 'В списке смерженных веток есть что-то лишнее');
            self::assertEmpty(array_intersect_key($branchListMerged, [
                Git::BRANCH_NAME_MASTER => 1,
                Git::BRANCH_NAME_HEAD,
            ]), 'В списке смерженных веток есть мастер или хэд');
        }

        $historyExpected = array_merge(
            [$this->_gitCommand . ' branch -r 2>&1'],
            $this->_expectedCommandListMerged($this->_branchBeforeTest, Git::BRANCH_TYPE_REMOTE),
            [$this->_gitCommand . ' branch 2>&1'],
            $this->_expectedCommandListMerged($this->_branchBeforeTest, Git::BRANCH_TYPE_LOCAL, false)
        );
        self::assertEquals($historyExpected, $this->_executeHistory, 'Неправильный набор комманд для просмотра померженных веток');
    }

    /**
     * Возвращает список комманд, используемых для получения списка померженных в мастер веток
     *
     * @param string $branchBefore
     * @param string $type
     * @param bool $pull
     * @return string[]|bool
     */
    private function _expectedCommandListMerged(string $branchBefore, $type, $pull = true)
    {
        $expectedList = [];
        if ($branchBefore != Git::BRANCH_NAME_MASTER) {
            $expectedList[] = $this->_gitCommand . ' checkout master 2>&1';
        }
        if ($pull) {
            $expectedList[] = $this->_gitCommand . ' pull 2>&1';
        }
        if ($type == Git::BRANCH_TYPE_REMOTE) {
            $expectedList[] = $this->_gitCommand . ' for-each-ref --format="%(refname) %(authordate:short)" refs/remotes/origin --merged 2>&1';
        } elseif ($type == Git::BRANCH_TYPE_LOCAL) {
            $expectedList[] = $this->_gitCommand . ' for-each-ref --format="%(refname) %(authordate:short)" refs/heads --merged 2>&1';
        } else {
            return false;
        }
        if ($branchBefore != Git::BRANCH_NAME_MASTER) {
            $expectedList[] = $this->_gitCommand . ' checkout ' . $branchBefore . ' 2>&1';
        }
        return $expectedList;
    }

    /**
     * Тестирует, что используется правильная команда для обновления ссылок
     */
    public function testUpdateRefs(): void
    {
        $this->_mockExecute();
        $this->_git->updateRefs();
        self::assertEquals([$this->_gitCommand . ' remote update --prune 2>&1'], $this->_executeHistory, 'Неправильная команда обновления');
    }

    /**
     * Тест чекаута
     */
    public function testCheckout(): void
    {
        $this->_mockExecute();

        $this->_executeHistory = [];
        self::assertFalse($this->_git->checkout('someUnexistentBranchName'), 'Чекаут в несуществующую ветку вернул true');
        self::assertEquals([$this->_gitCommand . ' branch -a 2>&1'], $this->_executeHistory, 'Чекаут в несуществующую ветку вызывал неправильные команды');

        $this->_executeHistory = [];
        $currentBranch = $this->_git->getCurrentBranchName();
        self::assertTrue($this->_git->checkout($currentBranch), 'Чекаут в текущую ветку вернул false');
        self::assertEquals([], $this->_executeHistory, 'Чекаут в текущую ветку вызывал неправильные команды');

        $otherBranches = array_diff($this->_git->getBranchList(Git::BRANCH_TYPE_ALL), [$currentBranch]);
        if (empty($otherBranches)) {
            return;
        }
        $checkoutTo = array_pop($otherBranches);

        $this->_executeHistory = [];
        self::assertTrue($this->_git->checkout($checkoutTo), 'Чекаут в другую ветку вернул false');
        self::assertEquals([
            $this->_gitCommand . ' branch -a 2>&1',
            $this->_gitCommand . ' checkout ' . $checkoutTo . ' 2>&1',
        ], $this->_executeHistory, 'Чекаут в другую ветку вызывал неправильные команды');
        self::assertEquals($checkoutTo, $this->_git->getCurrentBranchName(), 'Чекаут не перезаписал название текущей ветки');
    }


    /**
     * Тестирует функцию changeCurrentBranch (checkout + pull)
     */
    public function testChangeCurrentBranch(): void
    {
        $this->_mockExecute();

        $this->_executeHistory = [];
        self::assertFalse($this->_git->changeCurrentBranch('someUnexistentBranchName'), 'Переход в несуществующую ветку вернул true');
        self::assertEquals([$this->_gitCommand . ' branch -r 2>&1'], $this->_executeHistory, 'Переход в несуществующую ветку вызывал неправильные команды');

        $remoteBranches = $this->_git->getBranchList(Git::BRANCH_TYPE_REMOTE);

        $this->_executeHistory = [];
        $currentBranch = $this->_git->getCurrentBranchName();
        if (in_array($currentBranch, $remoteBranches)) {
            self::assertTrue($this->_git->changeCurrentBranch($currentBranch), 'Переход в текущую ветку вернул false');
            self::assertEquals([
                $this->_gitCommand . ' branch -r 2>&1',
                $this->_gitCommand . ' pull 2>&1',
            ], $this->_executeHistory, 'Переход в текущую ветку вызывал неправильные команды');
        }

        $otherBranches = array_diff($remoteBranches, [$currentBranch]);
        if (empty($otherBranches)) {
            return;
        }
        $this->_executeHistory = [];
        $checkoutTo = array_pop($otherBranches);
        self::assertTrue($this->_git->changeCurrentBranch($checkoutTo), 'Переход в другую ветку вернул false');
        self::assertEquals([
            $this->_gitCommand . ' branch -r 2>&1',
            $this->_gitCommand . ' checkout ' . $checkoutTo . ' 2>&1',
            $this->_gitCommand . ' pull 2>&1',
        ], $this->_executeHistory, 'Переход в другую ветку вызывал неправильные команды');
    }


    /**
     * Возвращает список комманд, используемых для удаления ветки
     *
     * @param string $branchDelete
     * @param bool $canDelete
     * @param string $branchBefore
     * @param string $type
     * @param bool $pull
     * @return string[]|bool
     */
    private function _expectedCommandListDelete(string $branchDelete, bool $canDelete, string $branchBefore, string $type, bool $pull = true)
    {
        if (($branchDelete == $branchBefore) && ($type == Git::BRANCH_TYPE_LOCAL)) {
            return [];
        }
        $expectedList = $this->_expectedCommandListMerged($branchBefore, $type, $pull);
        if (empty($expectedList)) {
            return false;
        }
        if (!$canDelete) {
            return $expectedList;
        }
        if ($branchBefore != Git::BRANCH_NAME_MASTER) {
            $expectedList[] = $this->_gitCommand . ' checkout master 2>&1';
        }
        if ($type == Git::BRANCH_TYPE_REMOTE) {
            $expectedList[] = $this->_gitCommand . ' push origin --delete ' . $branchDelete . ' 2>&1';
        } elseif ($type == Git::BRANCH_TYPE_LOCAL) {
            $expectedList[] = $this->_gitCommand . ' branch ' . $branchDelete . ' -d 2>&1';
        } else {
            return false;
        }
        if ($branchBefore != Git::BRANCH_NAME_MASTER) {
            $expectedList[] = $this->_gitCommand . ' checkout ' . $branchBefore . ' 2>&1';
        }
        return $expectedList;
    }


    /**
     * Тест удаления обычных веток
     */
    public function testDeleteNormal(): void
    {
        $this->_mockExecute();

        $unexistentBranchName = 'someUnexistentBranchName';
        $pull = true;
        foreach ([Git::BRANCH_TYPE_LOCAL, Git::BRANCH_TYPE_REMOTE] as $type) {
            $this->_executeHistory = [];
            $labelType = (($type == Git::BRANCH_TYPE_REMOTE) ? 'не' : '') . 'локальной';
            self::assertFalse(
                $this->_git->deleteBranch($unexistentBranchName, $type),
                'Удаление несуществующей ' . $labelType . ' ветки вернуло true'
            );
            self::assertEquals(
                $this->_expectedCommandListDelete($unexistentBranchName, false, $this->_branchBeforeTest, $type, $pull),
                $this->_executeHistory,
                'Удаление несуществующей ' . $labelType . ' ветки вызывало неправильные команды'
            );
            $pull = false;

            $branches = ['merged' => [], 'unmerged' => []];
            $all = $this->_git->getBranchList($type);
            $branches['merged'] = array_diff(array_keys($this->_git->getMergedBranches($type)), [$this->_branchBeforeTest]);
            $branches['unmerged'] = array_diff($all, [
                Git::BRANCH_NAME_MASTER,
                Git::BRANCH_NAME_HEAD,
                $this->_branchBeforeTest,
            ], $branches['merged']);
            $this->_executeHistory = [];

            foreach ($branches as $state => $list) {
                if (empty($list)) {
                    continue;
                }
                $this->_executeHistory = [];
                $canDelete = ($state === 'merged');
                $labelState = (($state === 'unmerged') ? 'не' : '') . 'померженной';
                $branch = array_pop($list);
                self::assertEquals(
                    $canDelete,
                    $this->_git->deleteBranch($branch, $type),
                    'Удаление ' . $labelState . ' ' . $labelType . ' ветки вернуло не то, что нужно'
                );
                self::assertEquals(
                    $this->_expectedCommandListDelete($branch, $canDelete, $this->_branchBeforeTest, $type, $pull),
                    $this->_executeHistory,
                    'Удаление ' . $labelState . ' ' . $labelType . ' ветки вызывало неправильные команды'
                );
            }
        }
    }

    /**
     * Тест удаления текущей ветки
     * @group Git
     */
    public function testDeleteCurrent(): void
    {
        if ($this->_branchBeforeTest == Git::BRANCH_NAME_MASTER) {
            return;
        }
        $this->_mockExecute();

        self::assertFalse(
            $this->_git->deleteBranch($this->_branchBeforeTest, Git::BRANCH_TYPE_LOCAL),
            'Удаление текущей локальной ветки вернуло true'
        );
        self::assertEquals(
            [],
            $this->_executeHistory,
            'Удаление текущей локальной ветки вызывало какие-то команды'
        );

        $mergedBranches = $this->_git->getMergedBranches(Git::BRANCH_TYPE_REMOTE);
        $hasMergedRemote = !empty($mergedBranches[$this->_branchBeforeTest]);
        $this->_executeHistory = [];

        $label = 'которая ' . ($hasMergedRemote ? '' : 'не') . 'существует';
        self::assertEquals(
            $hasMergedRemote,
            $this->_git->deleteBranch($this->_branchBeforeTest, Git::BRANCH_TYPE_REMOTE),
            'Удаление текущей нелокальной ветки (' . $label . ') вернуло не то, что нужно'
        );
        self::assertEquals(
            $this->_expectedCommandListDelete($this->_branchBeforeTest, $hasMergedRemote, $this->_branchBeforeTest, Git::BRANCH_TYPE_REMOTE, false),
            $this->_executeHistory,
            'Удаление текущей нелокальной ветки (' . $label . ') вызывало неправильные команды'
        );
    }

    /**
     * Тест удаления мастера или хеда
     */
    public function testDeleteMain(): void
    {
        $this->_mockExecute(0);
        foreach ([Git::BRANCH_TYPE_REMOTE, Git::BRANCH_TYPE_LOCAL] as $type) {
            foreach ([Git::BRANCH_NAME_MASTER, Git::BRANCH_NAME_HEAD] as $branch) {
                $this->_executeHistory = [];
                self::assertFalse(
                    $this->_git->deleteBranch($branch, $type),
                    'Удаление ветки ' . $branch . ' вернуло true'
                );
                self::assertEquals(
                    [],
                    $this->_executeHistory,
                    'Удаление ветки ' . $branch . ' вызывало какие-то команды'
                );
            }
        }
    }

    /**
     * Выполнение команд, когда директория гита не является текущей
     */
    public function testOtherFolder(): void
    {
        $gitFolder = ROOT;
        chdir(APP);
        $this->_mockExecute();

        $git = new Git($gitFolder);
        $git->updateRefs();
        $git->pullCurrentBranch();

        $commandStart = 'cd ' . escapeshellarg($gitFolder) . ' 2>&1 && (' . $this->_gitCommand . ' ';
        $expectedHistory = [
            $commandStart . 'rev-parse --abbrev-ref HEAD 2>&1)',
            $commandStart . 'remote update --prune 2>&1)',
            $commandStart . 'pull 2>&1)',
        ];
        self::assertEquals($expectedHistory, $this->_executeHistory);
    }

    /**
     * Мокаем выполнение консольных команд
     *
     * @param int $expectTimes
     * @throws \Exception
     */
    private function _mockExecute(int $expectTimes = MethodMockerEntity::EXPECT_CALL_ONCE): void
    {
        $this->_executeHistory = [];
        MethodMocker::mock(Shell::class, '_exec')
            ->expectCall($expectTimes)
            ->willReturnAction(function ($args) {
                $this->_executeHistory[] = $args[0];
                if (preg_match('/^(cd [^&]+(&1)?\s+&&\s\()?' . addcslashes($this->_gitCommand, '/.') . ' (branch( -[ar])?|for-each-ref.*|rev-parse .*)/', $args[0])) {
                    exec($args[0], $output, $returnCode);
                    return [$returnCode === 0, $output];
                } else {
                    return [true, []];
                }
            });
    }

    /**
     * Распарсить запрос с хука гитхаба
     */
    public function testParseRequest(): void
    {
        $request = [
            'payload' => '{"ref":"refs/heads/master","after":"9876abcd","repository":{"id":12345,"name":"common","full_name":"ArtSkills/common"}}',
        ];
        $expectedResult = [
            'repo' => 'common',
            'branch' => 'master',
            'commit' => '9876abcd',
        ];
        self::assertEquals($expectedResult, Git::parseGithubRequest($request));
    }
}
