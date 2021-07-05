<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\Filesystem\FileTest;

use ArtSkills\Filesystem\File;
use ArtSkills\Filesystem\Folder;
use ArtSkills\TestSuite\AppTestCase;

class FileTest extends AppTestCase
{

    /**
     * Тест зиповки
     * @SuppressWarnings(PHPMD.FunctionRule)
     */
    public function testZip(): void
    {
        $testFile1 = __DIR__ . '/temptest.txt';
        file_put_contents($testFile1, 'temptest');
        $zipName = __DIR__ . '/actual_zip_1.zip';
        File::zip($testFile1, $zipName);
        self::assertFileExists($zipName, 'ZIP Файл не был создан.');
        unlink($zipName);

        $testFile2 = __DIR__ . '/temptest1.txt';
        $testFile3 = __DIR__ . '/temptest2.txt';
        file_put_contents($testFile2, 'temptest1');
        file_put_contents($testFile3, 'temptest2');
        $zipName = __DIR__ . '/actual_zip_2.zip';
        File::zip([$testFile2, $testFile3], $zipName);
        self::assertFileExists($zipName, 'ZIP Файл с несколькими файлами не был создан.');
        unlink($zipName);

        unlink($testFile1);
        unlink($testFile2);
        unlink($testFile3);
    }

    /**
     * Тест распаковки
     */
    public function testUnzip(): void
    {
        File::unZip(__DIR__ . '/to_unzip.tar.gz', __DIR__ . '/targz');
        $folder = new Folder(__DIR__ . '/targz');
        self::assertEquals(['f81ff56c9a.json'], $folder->find(), 'Не распаковался архив tar.gz');
        $folder->delete();

        File::unZip(__DIR__ . '/to_unzip.zip', __DIR__ . '/zip');
        $folder = new Folder(__DIR__ . '/zip');
        self::assertEquals(['unzippedFile.txt'], $folder->find(), 'Не распаковался архив zip');
        $folder->delete();
    }
}
