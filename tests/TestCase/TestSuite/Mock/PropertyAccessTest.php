<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\TestSuite\Mock;

use ArtSkills\TestSuite\Mock\PropertyAccess;
use ArtSkills\Test\TestCase\TestSuite\Mock\Fixture\MockTestFixture;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ArtSkills\TestSuite\Mock\PropertyAccess
 */
class PropertyAccessTest extends TestCase
{
	/** тест чтения и записи */
	public function test()
	{
		$testObject = new MockTestFixture();
		self::assertSame('testProtected', PropertyAccess::get($testObject, '_protectedProperty'), 'Не прочиталось protected свойство');
		self::assertSame('testPrivateStatic', PropertyAccess::getStatic(MockTestFixture::class, '_privateProperty'), 'Не прочиталось private static свойство');

		$newValue = 'newTestValue';
		PropertyAccess::set($testObject, '_protectedProperty', $newValue);
		self::assertSame($newValue, PropertyAccess::get($testObject, '_protectedProperty'), 'Не записалось protected свойство');

		$newStaticValue = 'newTestStaticValue';
		PropertyAccess::setStatic(MockTestFixture::class, '_privateProperty', $newStaticValue);
		self::assertSame($newStaticValue, PropertyAccess::getStatic(MockTestFixture::class, '_privateProperty'), 'Не записалось private static свойство');
	}

	/**
	 * тест несуществующего свойства
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage does not exist
	 */
	public function testBadProperty()
	{
		PropertyAccess::setStatic(MockTestFixture::class, '_unexistent', 'asd');
	}

	/**
	 * Изменение статических свойств с возможностью восстановления
	 */
	public function testStaticRestore()
	{
		$className = MockTestFixture::class;
		$propertyName = '_privateProperty';

		$originalValue = PropertyAccess::getStatic($className, $propertyName);

		$newStaticValue = $originalValue . 'newTestStaticValue';
		PropertyAccess::setStaticAndRestore($className, $propertyName, $newStaticValue);
		self::assertSame($newStaticValue, PropertyAccess::getStatic($className, $propertyName));

		$newStaticValue .= 'evenNewerStaticValue';
		PropertyAccess::setStaticAndRestore($className, $propertyName, $newStaticValue);
		self::assertSame($newStaticValue, PropertyAccess::getStatic($className, $propertyName));

		PropertyAccess::restoreStatic($className, $propertyName);
		self::assertSame($originalValue, PropertyAccess::getStatic($className, $propertyName));
	}

	/**
	 * Восстановление свойства, которое не было изменено
	 *
	 * @expectedException \PHPUnit\Framework\AssertionFailedError
	 * @expectedExceptionMessage MockTestFixture::_privateProperty was not modified
	 */
	public function testRestoreNotModified()
	{
		PropertyAccess::restoreStatic(MockTestFixture::class, '_privateProperty');
	}

	/**
	 * Восстановление свойства 2 раза
	 *
	 * @expectedException \PHPUnit\Framework\AssertionFailedError
	 * @expectedExceptionMessage MockTestFixture::_privateProperty was not modified
	 */
	public function testRestoreTwice()
	{
		$className = MockTestFixture::class;
		$propertyName = '_privateProperty';

		PropertyAccess::setStaticAndRestore($className, $propertyName, 'aedrjhnbaeoridhno');
		PropertyAccess::restoreStatic($className, $propertyName);
		PropertyAccess::restoreStatic($className, $propertyName);
	}

	/**
	 * Восстановление всех статических свойств
	 */
	public function testStaticRestoreAll()
	{
		$className = MockTestFixture::class;
		$originalValue = PropertyAccess::getStatic($className, '_privateProperty');
		PropertyAccess::setStatic($className, '_otherProperty', $originalValue);

		self::assertSame($originalValue, PropertyAccess::getStatic($className, '_privateProperty'));
		self::assertSame($originalValue, PropertyAccess::getStatic($className, '_otherProperty'));

		$newValue = $originalValue . 'newTestStaticValue';
		PropertyAccess::setStaticAndRestore($className, '_privateProperty', $newValue);
		PropertyAccess::setStaticAndRestore($className, '_otherProperty', $newValue);
		self::assertSame($newValue, PropertyAccess::getStatic($className, '_privateProperty'));
		self::assertSame($newValue, PropertyAccess::getStatic($className, '_otherProperty'));

		PropertyAccess::restoreStaticAll();
		self::assertSame($originalValue, PropertyAccess::getStatic($className, '_privateProperty'));
		self::assertSame($originalValue, PropertyAccess::getStatic($className, '_otherProperty'));

		// restoreStaticAll() можно вызывать несколько раз без ошибок
		PropertyAccess::restoreStaticAll();
	}

	/**
	 * Восстановление свойства после восстановления всего
	 *
	 * @expectedException \PHPUnit\Framework\AssertionFailedError
	 * @expectedExceptionMessage MockTestFixture::_privateProperty was not modified
	 */
	public function testRestoreAfterRestoreAll()
	{
		$className = MockTestFixture::class;
		$propertyName = '_privateProperty';

		PropertyAccess::setStaticAndRestore($className, $propertyName, 'aedrjhnbaeoridhno');
		PropertyAccess::restoreStaticAll();
		PropertyAccess::restoreStatic($className, $propertyName);
	}
}
