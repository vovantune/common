<?php
declare(strict_types=1);

namespace App\Test\TestCase\Shell\ValueObjectDocumentationShellTest\Entities;

/**
 * @OA\Schema()
 */
class ObjectResponse extends ObjectParentResponse
{
    /**
     * @OA\Property
     * @var float[]
     */
    public $arrNumberProp;

    /**
     * @OA\Property
     * @var Object1[]
     */
    public $arrObjectProp;
}
