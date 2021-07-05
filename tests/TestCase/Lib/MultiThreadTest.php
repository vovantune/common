<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\Lib;

use ArtSkills\Lib\Env;
use ArtSkills\Lib\MultiThreads;
use ArtSkills\TestSuite\AppTestCase;

class MultiThreadTest extends AppTestCase
{
    /** Многопоточный запуск */
    public function test(): void
    {
        $mt = MultiThreads::getInstance();

        $maxThreads = (int)Env::getThreadsLimit();
        $maxTests = 10;
        for ($i = 0; $i < $maxTests; $i++) {
            $mt->run(function () {
                usleep(rand(0, 1000));
            });
            self::assertLessThanOrEqual($maxThreads, $mt->getTotalThreads());
        }
        $mt->waitThreads();
    }
}
