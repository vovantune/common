<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\Shell\ValueObjectDocumentationShellTest;

use ArtSkills\Shell\ValueObjectDocumentationShell;
use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\TestSuite\Mock\MethodMocker;
use Cake\Console\Shell;
use Cake\Log\Log;

class ValueObjectDocumentationShellTest extends AppTestCase
{
    /**
     * @throws \Exception
     */
    public function testMain(): void
    {
        $shell = new ValueObjectDocumentationShell();
        $resultFile = __DIR__ . DS . 'results.txt';
        file_put_contents($resultFile, '1');

        MethodMocker::mock(Shell::class, 'out')
            ->singleCall()
            ->expectArgs("You has new data, sync $resultFile from server");

        MethodMocker::mock(Log::class, 'error')
            ->singleCall()
            ->expectArgs('Incorrect property type for App\Test\TestCase\Shell\ValueObjectDocumentationShellTest\Entities\Object1::prop1');

        $shell->main(__DIR__ . DS . 'Entities', $resultFile);
        self::assertFileEquals(__DIR__ . DS . 'expected.txt', $resultFile);
        unlink($resultFile);
    }
}
