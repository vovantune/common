<?php

namespace ArtSkills\TestSuite\Fixture;

use PHPUnit\Framework\TestSuite;

class FixtureInjector extends \Cake\TestSuite\Fixture\FixtureInjector
{
	/**
	 * @inheritdoc
	 */
	public function startTestSuite(TestSuite $suite)
	{
		// сделано специально
	}

	/**
	 * @inheritdoc
	 */
	public function endTestSuite(TestSuite $suite)
	{
		$this->_fixtureManager->shutDown();
	}
}