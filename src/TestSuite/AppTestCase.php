<?php

namespace ArtSkills\TestSuite;

use Cake\TestSuite\TestCase;

abstract class AppTestCase extends TestCase
{
	use TestCaseTrait;

	/** @inheritdoc */
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();
		self::_setUpBeforeClass();
	}

	/** @inheritdoc */
	public function setUp()
	{
		parent::setUp();
		$this->_setUp();
	}

	/** @inheritdoc */
	public function tearDown()
	{
		parent::tearDown();
		$this->_tearDown();
	}
}
