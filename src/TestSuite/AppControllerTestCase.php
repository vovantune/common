<?php
namespace ArtSkills\TestSuite;

use ArtSkills\Cake\TestSuite\IntegrationTestCase;

abstract class AppControllerTestCase extends IntegrationTestCase
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
