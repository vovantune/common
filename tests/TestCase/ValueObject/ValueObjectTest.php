<?php
namespace ArtSkills\Test\TestCase\ValueObject;

use ArtSkills\TestSuite\AppTestCase;

class ValueObjectTest extends AppTestCase
{

	/**
	 * Цепочка вызовов и превращение в массив
	 */
	public function test() {
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
		];
		self::assertEquals($expectedArray, $obj->toArray());
		self::assertEquals(json_encode($expectedArray), json_encode($obj));

		$obj = ValueObjectFixture::create([
			'field2' => 'ololo',
			'field3' => 'azazaz',
		])->setField1('qqq');
		self::assertEquals('ololo', $obj->field2);
		self::assertEquals([
			'field1' => 'qqq',
			'field3' => 'azazaz',
		], $obj->toArray());

		self::assertEquals('{
    "field1": "qqq",
    "field3": "azazaz"
}', $obj->toJson());
	}

	/**
	 * плохой вызов магического метода
	 * @expectedException \Exception
	 * @expectedExceptionMessage Undefined property field4
	 */
	public function testBadProperty() {
		$obj = new ValueObjectFixture();
		$obj->setField4();
	}

	/**
	 * плохой вызов магического метода
	 * @expectedException \Exception
	 * @expectedExceptionMessage Invalid argument count when calling setField3
	 */
	public function testBadParams() {
		$obj = new ValueObjectFixture();
		$obj->setField3('asd', 'qwe');
	}

	/**
	 * Инициализация с несуществующим свойством
	 *
	 * @expectedException \Exception
	 * @expectExceptionMessage Property exported_bad does not exist!
	 */
	public function testBadInit() {
		new ValueObjectFixture(['exported_bad' => 1]);
	}


}