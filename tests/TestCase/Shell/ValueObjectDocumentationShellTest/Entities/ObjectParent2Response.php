<?php
declare(strict_types=1);

namespace App\Test\TestCase\Shell\ValueObjectDocumentationShellTest\Entities;

use ArtSkills\ValueObject\ValueObject;

/**
 * @OA\Schema()
 */
class ObjectParent2Response extends ValueObject
{
    /**
     * @OA\Property()
     * @var bool
     */
    public $boolProp;

    /**
     * @OA\Property(enum={1, 2})
     * @var int
     */
    public $intProp;
}
