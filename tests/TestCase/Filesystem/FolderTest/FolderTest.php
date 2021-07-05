<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\Filesystem\FolderTest;

use ArtSkills\Filesystem\Folder;
use ArtSkills\TestSuite\AppTestCase;

class FolderTest extends AppTestCase
{

    /**
     * Проверка чистилки файлов
     */
    public function testCleanupDirByLifetime(): void
    {
        $csvFile = __DIR__ . '/temp.csv';
        $pdfFile = __DIR__ . '/temp.pdf';
        $pdfNewFile = __DIR__ . '/tempNew.pdf';
        $pdfInFolderFile = __DIR__ . '/nonDelete/tempNew.pdf';

        file_put_contents($csvFile, 'This is csv file');
        file_put_contents($pdfFile, 'This is PDF file');
        Folder::createIfNotExists(__DIR__ . '/nonDelete');
        file_put_contents($pdfInFolderFile, 'This is PDF file');
        sleep(2);
        file_put_contents($pdfNewFile, 'This is PDF file');

        Folder::cleanupDirByLifetime(__DIR__, ['.*\.pdf'], 1, ['nonDelete/']);

        self::assertFileExists($csvFile, 'Файл был удалён, но такого не должно было случится');
        self::assertFileExists($pdfNewFile, 'Файл был удалён, но такого не должно было случится');
        self::assertFileExists($pdfInFolderFile, 'Файл был удалён, хотя стоит запрет на удаление из директории');
        self::assertFileNotExists($pdfFile, 'А этот файл должен был исчезнуть');
        self::assertFileExists(__FILE__, 'Тест удалил себя!!!');

        if (file_exists($pdfInFolderFile)) {
            unlink($pdfInFolderFile);
            rmdir(__DIR__ . '/nonDelete');
        }
    }
}
