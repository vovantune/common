<?php

namespace ArtSkills\Test\TestCase\ValueObject;

use ArtSkills\TestSuite\AppTestCase;
use Cake\I18n\Date;
use Cake\I18n\Time;

class ValueObjectTest extends AppTestCase
{

    /**
     * Цепочка вызовов и превращение в массив
     */
    public function test(): void
    {
        $obj = new ValueObjectFixture();
        self::assertEquals('asd', $obj->field1);
        self::assertEquals('qwe', $obj->field2);

        $obj->setField1('zxc')->setField3('vbn');
        self::assertEquals('zxc', $obj->field1);
        self::assertEquals('vbn', $obj->field3);

        $expectedArray = [
            'field1' => 'zxc',
            // field2 выключен
            'field3' => 'vbn',
            'field4' => null,
            'timeField' => null,
            'dateField' => null,
        ];
        self::assertEquals($expectedArray, $obj->toArray());
        self::assertEquals(json_encode($expectedArray), json_encode($obj));

        $obj = ValueObjectFixture::create([
            'field2' => 'ololo',
            'field3' => 'azazaz',
            'timeField' => '2020-04-01 16:15:00',
            'dateField' => '2021-11-01',
        ])->setField1('qqq');
        self::assertEquals('ololo', $obj->field2);
        self::assertEquals([
            'field1' => 'qqq',
            'field3' => 'azazaz',
            'field4' => null,
            'timeField' => '2020-04-01T16:15:00+03:00',
            'dateField' => '2021-11-01T00:00:00+00:00',
        ], $obj->toArray());

        self::assertEquals('{
    "field1": "qqq",
    "field3": "azazaz",
    "field4": null,
    "timeField": "2020-04-01T16:15:00+03:00",
    "dateField": "2021-11-01T00:00:00+00:00"
}', $obj->toJson());

        $timeString = '2020-04-02 18:21:00';
        $obj->setTimeField($timeString);
        self::assertEquals(Time::parse($timeString), $obj->timeField);

        $dateString = '2021-01-12';
        $obj->setDateField($dateString);
        self::assertEquals(Date::parse($dateString), $obj->dateField);
    }

    /**
     * плохой вызов магического метода
     */
    public function testBadProperty(): void
    {
        $this->expectExceptionMessage("Undefined property field5");
        $this->expectException(\Exception::class);
        $obj = new ValueObjectFixture();
        $obj->setField5(); // @phpstan-ignore-line
    }

    /**
     * плохой вызов магического метода
     */
    public function testBadParams(): void
    {
        $this->expectExceptionMessage("Invalid argument count when calling setField3");
        $this->expectException(\Exception::class);
        $obj = new ValueObjectFixture();
        $obj->setField3('asd', 'qwe'); // @phpstan-ignore-line
    }

    /**
     * Инициализация с несуществующим свойством
     */
    public function testBadInit(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Property exported_bad not exists!');
        new ValueObjectFixture(['exported_bad' => 1]);
    }
}
