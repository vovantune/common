<?php
namespace ArtSkills\Test\TestCase\Lib;

use ArtSkills\Lib\Strings;
use ArtSkills\TestSuite\AppTestCase;

class StringsTest extends AppTestCase
{

	/** проверка префикса */
	public function testStartsWith() {
		$prefix = 'prefix';
		$testStr = "{$prefix}asdfg";
		self::assertTrue(Strings::startsWith($testStr, $prefix));

		$testStr = "asd{$prefix}fg";
		self::assertFalse(Strings::startsWith($testStr, $prefix));
	}

	/** проверка постфикса */
	public function testEndsWith() {
		$postfix = 'prefix';
		$testStr = "asdfg{$postfix}";
		self::assertTrue(Strings::endsWith($testStr, $postfix));

		$testStr = "asd{$postfix}fg";
		self::assertFalse(Strings::endsWith($testStr, $postfix));
	}

	/** замена префикса */
	public function testReplacePrefix() {
		$prefix = 'prefix';
		$replacement = 'replacement';
		$testStr = "{$prefix}asdf{$prefix}";
		$expectedStr = "{$replacement}asdf{$prefix}";
		self::assertEquals($expectedStr, Strings::replacePrefix($testStr, $prefix, $replacement));
	}

	/** замена постфикса */
	public function testReplacePostfix() {
		$postfix = 'prefix';
		$replacement = 'replacement';
		$testStr = "{$postfix}asdf{$postfix}";
		$expectedStr = "{$postfix}asdf{$replacement}";
		self::assertEquals($expectedStr, Strings::replacePostfix($testStr, $postfix, $replacement));
	}


}
