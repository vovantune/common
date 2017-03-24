<?php

namespace ArtSkills\Filesystem;

use Cake\I18n\Time;

class Folder extends \Cake\Filesystem\Folder
{
	/**
	 * Путь к папке, которого может не существрвать
	 *
	 * @var string
	 */
	private $_virtualPath = null;

	/** @inheritdoc */
	public function __construct($path = null, $create = false, $mode = false) {
		parent::__construct($path, $create, $mode);
		$this->_virtualPath = $path;
	}

	/**
	 * Проверить, пуста папка или нет
	 *
	 * @return bool
	 */
	public function isEmpty() {
		if (empty($this->path)) {
			return false;
		}
		$contents = array_diff(scandir($this->path), ['.', '..']);
		return empty($contents);
	}

	/**
	 * Создать текущую папку
	 *
	 * @param int $mode
	 * @return bool
	 */
	public function createSelf($mode = 0755) {
		if (empty($this->_virtualPath)) {
			return false;
		} else {
			return $this->create($this->_virtualPath, $mode) && $this->cd($this->_virtualPath);
		}
	}

	/**
	 * Существует ли эта папка
	 *
	 * @return bool
	 */
	public function exists() {
		return !empty($this->path) && is_dir($this->path);
	}

	/** @inheritdoc */
	public function copy($options) {
		$res = parent::copy($options);
		$this->cd($this->_virtualPath);
		return $res;
	}

	/**
	 * Чистилка временных папок. Выбирает РЕКУРСИВНО файлы в папке $dirPath
	 * по шаблону $exp с временем жизни больше $lifetime и удаляет
	 *
	 * @param string $dirPath
	 * @param string|array $expressions регулярные выражения по которым надо чистить файл
	 * @param int $lifetime время жизни файла в секундах
	 * @param array $pathBlacklist исключить пути
	 */
	public static function cleanupDirByLifetime(
		$dirPath, $expressions = ['.*\.pdf'], $lifetime = 300, $pathBlacklist = []
	) {
		$currentTime = Time::now()->getTimestamp();
		$expressions = (array)$expressions;
		$dir = new self($dirPath);
		foreach ($expressions as $expression) {
			$files = $dir->findRecursive($expression);
			foreach ($files as $file) {
				foreach ($pathBlacklist as $black) {
					if (strpos($file, $black) !== false) {
						continue 2;
					}
				}
				$file = new File($file);
				if ($currentTime - $file->lastChange() >= $lifetime) {
					$file->delete();
				}
			}
		}
	}


	/**
	 * Создать папку, если такой нет
	 *
	 * @param string $path
	 * @param int $mode
	 */
	public static function createIfNotExists($path, $mode = 0755) {
		if (!is_dir($path)) {
			mkdir($path, 0755);
		}
	}

}