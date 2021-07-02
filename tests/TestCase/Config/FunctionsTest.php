<?php

namespace ArtSkills\Test\TestCase\Config;

use ArtSkills\TestSuite\AppTestCase;

class FunctionsTest extends AppTestCase
{

    /** функция для удобства использования вложенных ассоциаций */
    public function testAssoc(): void
    {
        self::assertEquals('Table1', assoc('Table1'));
        self::assertEquals(
            'Table1.Table2.Table3.Table4',
            assoc('Table1', 'Table2', 'Table3', 'Table4')
        );
    }

    /** функция для удобства использования полей с названием таблицы */
    public function testField(): void
    {
        self::assertEquals('Table.field', field('Table', 'field'));
        self::assertEquals('Table.field >=', field('Table', 'field', '>='));
    }

    /** функция для удобства формирования списка условий для where */
    public function testFieldsWhere(): void
    {
        $conditions = [
            'Table1' => [
                'field1' => 'val1',
                'field2' => 'val2',
            ],
            'Table2' => [
                'field1' => 'val3',
                'field2' => 'val4',
            ],
            '' => [
                'field3' => 'val',
            ],
        ];
        $expectedResult = [
            'Table1.field1' => 'val1',
            'Table1.field2' => 'val2',
            'Table2.field1' => 'val3',
            'Table2.field2' => 'val4',
            'field3' => 'val',
        ];
        self::assertEquals($expectedResult, fieldsWhere($conditions));
    }

    /**
     * функция для удобства формирования списка условий для where
     * дублирование ключей массива
     */
    public function testFieldsWhereDuplicate(): void
    {
        $this->expectExceptionMessage("Дублируется ключ Table1.field1");
        $this->expectException(\Exception::class);
        fieldsWhere([
            'Table1' => [
                'field1' => 'val1',
            ],
            '' => [
                'Table1.field1' => 'val',
            ],
        ]);
    }

    /** функция для удобства формирования списка полей для select */
    public function testFieldsSelect(): void
    {
        $fields = [
            'Table1' => [
                'field1',
                'field2',
            ],
            'Table2' => [
                'field1',
                'alias' => 'field2',
                'field3',
            ],
            '' => [
                'field4',
                'other_alias' => 'field5',
            ],
        ];
        $expectedResult = [
            0 => 'field4',
            'other_alias' => 'field5',
            2 => 'Table1.field1',
            3 => 'Table1.field2',
            4 => 'Table2.field1',
            'alias' => 'Table2.field2',
            5 => 'Table2.field3',
        ];
        self::assertEquals($expectedResult, fieldsSelect($fields));
    }

    /**
     * функция для удобства формирования списка полей для select
     * дублирование ключей массива
     */
    public function testFieldsSelectDuplicate(): void
    {
        $this->expectExceptionMessage("Дублируется ключ alias");
        $this->expectException(\Exception::class);
        fieldsSelect([
            'Table1' => [
                'alias' => 'field1',
            ],
            'Table2' => [
                'alias' => 'field2',
            ],
        ]);
    }
}
