<?php
namespace ArtSkills\TestSuite;

use Cake\TestSuite\TestCase;

abstract class AppTestCase extends TestCase
{
	use TestCaseTrait;

	/**
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		$this->setUpTest();
	}

	/**
	 * @inheritdoc
	 */
	public function tearDown() {
		parent::tearDown();
		$this->tearDownTest();
	}
}
