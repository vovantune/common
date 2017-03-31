<?php
namespace ArtSkills\Lib;
use Cake\I18n\Time;

/**
 * По смыслу это должен был быть Shell
 * Но для возможности наследования от AppShell проекта, я вынес это не как Shell
 */
class GitBranchTrim
{
	/**
	 * Из какой директории запускать
	 *
	 * @var string
	 */
	protected static $_fromDir = '';

	/**
	 * Возраст удаляемых веток
	 *
	 * @var int
	 */
	protected static $_branchDeleteInterval = '-7 days';

	/**
	 * Удаление старых неиспользуемых веток
	 */
	public static function run() {
		$git = static::_git();
		$currentBranch = $git->getCurrentBranchName();
		if (empty($currentBranch)) {
			throw new \Exception('Гит не инициализирован');
		}

		$currentDir = getcwd();
		$fromDir = static::_fromDir();
		if (!empty($fromDir)) {
			chdir($fromDir);
		}

		if ($currentBranch != Git::BRANCH_NAME_MASTER) {
			if (!$git->checkout(Git::BRANCH_NAME_MASTER)) {
				throw new \Exception('Не удалось переключиться на мастера');
			}
		}

		$git->updateRefs();
		$toDeleteTypes = [Git::BRANCH_TYPE_LOCAL];
		if (static::_canDeleteRemote()) {
			array_unshift($toDeleteTypes, Git::BRANCH_TYPE_REMOTE);
		}

		foreach ($toDeleteTypes as $type) {
			static::_deleteMergedOldBranches($type, [$currentBranch]);
		}

		if ($currentBranch != Git::BRANCH_NAME_MASTER) {
			$git->checkout($currentBranch);
		}

		if (!empty($fromDir)) {
			chdir($currentDir);
		}
	}

	/**
	 * Где можно удалять удалённые(не локальные) ветки
	 *
	 * @return bool
	 */
	protected static function _canDeleteRemote() {
		return Env::isTestServer() || Env::isUnitTest();
	}

	/**
	 * Объект гита
	 * @return Git
	 */
	protected static function _git() {
		return Git::getInstance();
	}

	/**
	 * Из какой директории запускать
	 *
	 * @return string
	 */
	protected static function _fromDir() {
		if (Env::isLocal() || Env::isUnitTest()) {
			return '';
		}
		return static::$_fromDir;
	}

	/**
	 * подчищает старые неиспользуемые ветки
	 *
	 * @param string $type
	 * @param string[] $skipBranches
	 */
	protected static function _deleteMergedOldBranches($type, $skipBranches = []) {
		$git = static::_git();
		$skipBranches = array_merge($skipBranches, [Git::BRANCH_NAME_MASTER, Git::BRANCH_NAME_HEAD]);
		$mergedBranches = $git->getMergedBranches($type);
		$deleteDateFrom = Time::now(static::$_branchDeleteInterval)->format('Y-m-d');
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
	}
}