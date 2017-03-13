<?php
namespace ArtSkills\Cake\TestSuite\Fixture;


class FixtureInjector extends \Cake\TestSuite\Fixture\FixtureInjector
{
	/**
	 * @inheritdoc
	 */
	public function startTestSuite(\PHPUnit_Framework_TestSuite $suite) {
		// сделано специально
	}

	/**
	 * @inheritdoc
	 */
	public function endTestSuite(\PHPUnit_Framework_TestSuite $suite) {
		$this->_fixtureManager->shutDown();
	}
}