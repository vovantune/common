<?php
namespace ArtSkills\Test\Fixture;

use ArtSkills\TestSuite\Fixture\TestFixture;

class TestTableTwoFixture extends TestFixture
{
	/** @inheritdoc */
	public $records = [
		['id' => '11', 'table_one_fk' => '1000'],
	];
}