<?php
declare(strict_types=1);

namespace App\Test\TestCase\Shell\ValueObjectDocumentationShellTest\Entities;

use ArtSkills\ValueObject\ValueObject;

/**
 * @OA\Schema(description="Тестовое свойство-объект")
 */
class Object1 extends ValueObject
{
    /**
     * Свойство 1
     * @OA\Property()
     *
     * @var int|string
     */
    public $prop1;

    /**
     * @OA\Property()
     * @var string
     */
    public $prop2;
}
