<?php
declare(strict_types=1);

namespace App\Test\TestCase\Shell\ValueObjectDocumentationShellTest\Entities;

/**
 * @OA\Schema()
 */
class ObjectParentResponse extends ObjectParent2Response
{
    /**
     * @OA\Property()
     * @var Object1
     */
    public $object1;

    /**
     * @OA\Property()
     * @var float
     */
    public $numberProp;
}
