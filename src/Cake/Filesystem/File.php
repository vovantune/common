<?php
namespace ArtSkills\Cake\Filesystem;

use ArtSkills\Lib\Strings;

class File extends \Cake\Filesystem\File
{

	const DOWNLOAD_PATH = WWW_ROOT . 'production' . DS;

	/**
	 * Зипует файлы
	 *
	 * @param string[]|string $files - файлы, которые нужно зипануть
	 * @param string|null $newFile - полное имя нового файла. если не передать, то будет self::DOWNLOAD_PATH . uniqid()
	 * @param bool $deleteOld - удалять ли файлы
	 * @return string Имя файла
	 */
	public static function zip($files, $newFile = null, $deleteOld = false) {
		$files = (array)$files;

		if (!is_dir(self::DOWNLOAD_PATH)) {
			mkdir(self::DOWNLOAD_PATH, 0755);
		}

		if (empty($newFile)) {
			$newFile = self::DOWNLOAD_PATH . uniqid();
		}
		if (!preg_match('/\.zip$/i', $newFile)) {
			$newFile .= '.zip';
		}

		$tmpDir = TMP . uniqid();
		mkdir($tmpDir);
		$zipFiles = [];
		$currentDir = getcwd();
		chdir($tmpDir);
		foreach ($files as $sourceFile) {
			$tmpFile = Strings::lastPart(DS, $sourceFile);
			$zipFiles[] = $tmpFile;
			if ($deleteOld) {
				rename($sourceFile, $tmpFile);
			} else {
				if (is_dir($sourceFile)) {
					exec("cp -r $sourceFile $tmpFile");
				} else {
					copy($sourceFile, $tmpFile);
				}
			}
		}
		if (file_exists($newFile)) {
			unlink($newFile);
		}

		exec('zip -r "' . $newFile . '" "' . implode('" "', $zipFiles) . '"');
		chdir($currentDir);
		exec("rm -rf $tmpDir");
		return $newFile;
	}

	/**
	 * Удаляем из пути переходы на подпапки
	 *
	 * @param string $filePath
	 * @return string
	 */
	public static function escapePath($filePath) {
		return str_replace(["../", ".."], "", $filePath);
	}
}
