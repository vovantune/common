<?php
namespace ArtSkills\Test\TestCase\ValueObject;

use ArtSkills\ValueObject\ValueObject;

/**
 * @method $this setField1(mixed $value)
 * @method $this setField2(mixed $value)
 * @method $this setField3(mixed $value)
 */
class ValueObjectFixture extends ValueObject
{
	const EXCLUDE_EXPORT_PROPS = [
		'field2'
	];

	public $field1 = 'asd';
	public $field2 = 'qwe';
	public $field3;

}