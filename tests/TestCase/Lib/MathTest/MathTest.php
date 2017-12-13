<?php

namespace ArtSkills\Test\TestCase\Lib\MathTest;


use ArtSkills\Lib\Math;
use ArtSkills\TestSuite\AppTestCase;

class MathTest extends AppTestCase
{
	/** Тест округления */
	public function testRoundUpToNearest()
	{
		$testNumber = 0.5;
		self::assertEquals($testNumber, Math::roundUpToNearest($testNumber, 0));

		$testNumber = 0;
		$precision = 0.5;
		self::assertEquals($testNumber, Math::roundUpToNearest($testNumber, $precision));

		$testNumber = 0.1;
		$precision = 0.5;
		self::assertEquals(0.5, Math::roundUpToNearest($testNumber, $precision));

		$precision = 1;
		self::assertEquals(1, Math::roundUpToNearest($testNumber, $precision));

		$testNumber = 2.01;
		$precision = 0.5;
		self::assertEquals(2.5, Math::roundUpToNearest($testNumber, $precision));

		$testNumber = 2.0;
		self::assertEquals($testNumber, Math::roundUpToNearest($testNumber, $precision));

		$testNumber = 105.5;
		$precision = 100;
		self::assertEquals(200, Math::roundUpToNearest($testNumber, $precision));

	}
}