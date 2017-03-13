<?php
namespace ArtSkills\Cake\Filesystem;


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

}