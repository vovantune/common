<?php

namespace ArtSkills\Test\TestCase\Database\Type;

use ArtSkills\Database\Type\JsonType;
use ArtSkills\TestSuite\AppTestCase;
use Cake\Database\Type;
use Cake\ORM\Table;

/**
 * @property Table $TestTableFive
 */
class JsonTypeTest extends AppTestCase
{
    /** @inheritdoc */
    public $fixtures = [
        'TestTableFive',
    ];

    /** @inheritdoc */
    public function tearDown()
    {
        parent::tearDown();
        Type::set('json', new Type\JsonType('json'));
    }

    /**
     * Сохранение значения Null в поле типа JSON
     */
    public function testSaveNull()
    {
        Type::map('json', Type\JsonType::class);
        $newEntity = $this->TestTableFive->newEntity([
            'col_json' => null,
        ]);
        $res = $this->TestTableFive->save($newEntity);
        self::assertNotEmpty($res);
        self::assertNull($res->col_json);
        $dbValueIsNull = $this->TestTableFive->exists(['col_json IS NULL']);
        self::assertFalse($dbValueIsNull, 'Если этот ассерт завалился, то переопределять JsonType уже не нужно');

        Type::set('json', new JsonType('json'));
        $newEntity = $this->TestTableFive->newEntity([
            'col_json' => null,
        ]);
        $res = $this->TestTableFive->save($newEntity);
        self::assertNotEmpty($res);
        self::assertNull($res->col_json);
        $dbValueIsNull = $this->TestTableFive->exists(['col_json IS NULL']);
        self::assertTrue($dbValueIsNull, 'Неправильно работает переопределение JsonType');
    }
}
