<?php
declare(strict_types=1);

namespace ArtSkills\Filesystem;

use Cake\I18n\Time;

/**
 * @SuppressWarnings(PHPMD.MethodMix)
 */
class Folder extends \Cake\Filesystem\Folder
{
    /**
     * Путь к папке, которого может не существрвать
     *
     * @var string
     */
    private ?string $_virtualPath = null;

    /** @inheritdoc */
    public function __construct($path = null, $create = false, $mode = false)
    {
        parent::__construct($path, $create, $mode);
        $this->_virtualPath = $path;
    }

    /**
     * Проверить, пуста папка или нет
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
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
    public function createSelf(int $mode = 0755): bool
    {
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
    public function exists(): bool
    {
        return !empty($this->path) && is_dir($this->path);
    }

    /**
     * @inheritdoc
     * @phpstan-ignore-next-line
     */
    public function copy($options): bool
    {
        $res = parent::copy($options);
        $this->cd($this->_virtualPath);
        return $res;
    }

    /**
     * @inheritdoc
     * @phpstan-ignore-next-line
     */
    public function move($options): bool
    {
        $res = parent::move($options);
        $this->path = $this->_virtualPath;
        return $res;
    }

    /**
     * Чистилка временных папок. Выбирает РЕКУРСИВНО файлы в папке $dirPath
     * по шаблону $exp с временем жизни больше $lifetime и удаляет
     *
     * @param string $dirPath
     * @param string|string[] $expressions регулярные выражения по которым надо чистить файл
     * @param int $lifetime время жизни файла в секундах
     * @param string[] $pathBlacklist исключить пути
     * @return void
     */
    public static function cleanupDirByLifetime(
        string $dirPath,
        $expressions = ['.*\.pdf'],
        int $lifetime = 300,
        array $pathBlacklist = []
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
     * @return string
     */
    public static function createIfNotExists(string $path, int $mode = 0755): string
    {
        if (!is_dir($path)) {
            mkdir($path, $mode);
        }
        return $path;
    }
}
