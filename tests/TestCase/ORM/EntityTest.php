<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\ORM;

use ArtSkills\TestSuite\AppTestCase;
use TestApp\Model\Table\TestTableOneTable;
use TestApp\Model\Table\TestTableTwoTable;

/**
 * @property TestTableOneTable $TestTableOne
 * @property TestTableTwoTable $TestTableTwo
 */
class EntityTest extends AppTestCase
{

    /** @inheritdoc */
    public $fixtures = [
        'app.test_table_one',
        'app.test_table_two',
    ];

    /** проверка на изменение значения поля */
    public function testChanged(): void
    {
        $entity = $this->TestTableTwo->get(88);
        $entity->table_one_fk = $entity->table_one_fk;
        self::assertTrue($entity->isDirty('table_one_fk'));
        self::assertFalse($entity->changed('table_one_fk'));
    }

    /** удаление дочерней сущности */
    public function testDeleteChild(): void
    {
        $assocName = 'TestTableTwo';
        $childIds = [88, 90];

        $entity = $this->TestTableOne->get(45, ['contain' => $assocName]);
        self::assertEquals($childIds, array_column($entity->toArray()[$assocName], 'id'));

        $childIndex = 0;
        $entity->deleteChild($assocName, $childIndex); // @phpstan-ignore-line
        unset($childIds[$childIndex]);
        self::assertEquals(array_values($childIds), array_column($entity->toArray()[$assocName], 'id'));
        self::assertTrue($entity->isDirty($assocName));
    }

    /**
     * удаление несуществующей дочерней сущности
     */
    public function testDeleteChildNotExists(): void
    {
        $this->expectExceptionMessage("Unknown property TestTableTwo");
        $this->expectException(\Exception::class);
        $entity = $this->TestTableOne->get(45);
        $entity->deleteChild('TestTableTwo', 0); // @phpstan-ignore-line
    }
}
