<?php
namespace ArtSkills\Lib;

use ArtSkills\Traits\Singleton;
use Cake\Log\Log;

class Deploy
{
	use Singleton;

	/**
	 * Корень проекта
	 *
	 * @var string
	 */
	protected $_rootFolder = '';

	/**
	 * Название репозитория
	 *
	 * @var string
	 */
	protected $_repoName = '';

	/**
	 * Файл с версией
	 *
	 * @var string
	 */
	protected $_versionFile = '';


	/**
	 * Список зависимостей композера
	 *
	 * @var string[]
	 */
	protected $_composerDependencies = [
		'artskills/common',
	];

	/**
	 * Команда запуска композера
	 *
	 * @var string
	 */
	protected $_composerCommand = 'php composer.phar';

	/**
	 * Домашняя папка, без неё композер не работает
	 *
	 * @var string
	 */
	protected $_composerHome = '/var/www';

	/**
	 * Команда запуска композера
	 *
	 * @var string
	 */
	protected $_migrateCommand = 'vendor/bin/phinx migrate';

	/**
	 * Куда писать результат
	 *
	 * @var string
	 */
	protected $_logScope = 'deployment';

	/**
	 * вывод команд
	 *
	 * @var string[]
	 */
	protected $_output = [];


	/**
	 * Деплой
	 *
	 * @param string $repo обновляемая репа
	 * @param string $branch обновляемая ветка
	 * @param string $commit к чему обновляемся. для замиси в лог
	 * @param null|int $currentVersion счётчик версий
	 * @return bool
	 */
	public function run($repo, $branch, $commit, $currentVersion = null) {
		if (!$this->_canDeploy($repo, $branch)) {
			return false;
		}
		$currentDir = getcwd();
		chdir($this->_rootFolder);
		$timeStart = microtime(true);

		$this->_updateComposer();

		$this->_addToOutput(["\n\nGit pull\n"]);
		$this->_addToOutput($this->_git()->pullCurrentBranch()[1]);

		// обновление репозитория должно быть до миграции, чтобы подтянулись файлы миграции
		$this->_migrateDb();

		AppCache::flush();
		$this->_updateVersion($currentVersion);
		$this->_localChanges();

		$timeEnd = microtime(true);
		chdir($currentDir);
		$this->_log($timeStart, $timeEnd, $commit);
		return true;
	}

	/**
	 * Объект git
	 * @return Git
	 */
	protected function _git() {
		return Git::getInstance();
	}


	/**
	 * Можно ли деплоить
	 *
	 * @param string $repo
	 * @param string $branch
	 * @return bool
	 */
	protected function _canDeploy($repo, $branch) {
		return (
			// всё проинициализировано
			!empty($this->_rootFolder)
			&& !empty($this->_repoName)
			&& !empty($this->_git()->getCurrentBranchName())
			// и всё совпадает
			&& ($repo === $this->_repoName)
			&& ($branch === $this->_git()->getCurrentBranchName())
		);
	}

	/**
	 * обновить зависимости композера
	 */
	protected function _updateComposer() {
		$this->_addToOutput(["\n\nComposer\n"]);
		if (empty($this->_composerDependencies) || empty($this->_composerCommand)) {
			$this->_addToOutput(['not updating dependencies']);
			return;
		}
		if (!empty($this->_composerHome)) {
			putenv('HOME=' . $this->_composerHome);
		}
		$this->_exec($this->_composerCommand . ' "' . implode('" "', $this->_composerDependencies) . '"');
	}

	/**
	 * Выполнить и вернуть результат
	 *
	 * @param string $command
	 */
	protected function _exec($command) {
		exec($command . ' 2>&1', $output);
		$this->_addToOutput([$command]);
		$this->_addToOutput($output);
	}

	/**
	 * Запоминаем вывод для дальнейшего лога
	 *
	 * @param string[] $output
	 */
	protected function _addToOutput($output) {
		$this->_output = array_merge($this->_output, $output);
	}

	/**
	 * Запустить миграции
	 */
	protected function _migrateDb() {
		$this->_addToOutput(["\n\nMigration\n"]);
		// на тесте лучше мигрировать руками
		if (!Env::isTestServer() && !empty($this->_migrateCommand)) {
			$this->_exec($this->_migrateCommand);
		} else {
			$this->_addToOutput(['migration was not run']);
		}
	}

	/**
	 * Обновить файл с версией
	 * @param int|null $currentVersion
	 */
	protected function _updateVersion($currentVersion) {
		if (!empty($this->_versionFile) && ($currentVersion !== null)) {
			file_put_contents($this->_versionFile, ++$currentVersion);
		}
	}

	/**
	 * Если нужно выполнить ещё что-то, относящееся только к текущему репозиторию
	 */
	protected function _localChanges() {
		// noop
	}

	/**
	 * Записать результат в лог
	 *
	 * @param float $timeStart
	 * @param float $timeEnd
	 * @param float $commit
	 */
	protected function _log($timeStart, $timeEnd, $commit) {
		$this->_output = array_merge([
			date('Y-m-d H:i:s', $timeStart) . ': Deployment to ' . $commit,
			'Finished in ' . round($timeEnd - $timeStart, 3) . ' seconds',
		], $this->_output, ["\n\n"]);
		Log::info(implode("\n", $this->_output), $this->_logScope);

		$this->_output = [];
	}









}