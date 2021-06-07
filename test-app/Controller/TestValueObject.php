<?php
declare(strict_types=1);

namespace TestApp\Controller;

use ArtSkills\ValueObject\ValueObject;

class TestValueObject extends ValueObject
{
    /** @var string */
    public $testProperty = 'testData';
}
