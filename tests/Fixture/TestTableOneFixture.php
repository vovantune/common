<?php
declare(strict_types=1);

namespace ArtSkills\Test\Fixture;

use ArtSkills\TestSuite\Fixture\TestFixture;

class TestTableOneFixture extends TestFixture
{
    /**
     * @inheritdoc
     * @phpstan-ignore-next-line
     */
    public $records = [
        ['id' => '10000', 'col_enum' => 'val1', 'col_text' => 'test test test', 'col_time' => '2017-03-14 00:11:22'],
    ];
}
