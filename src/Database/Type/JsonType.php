<?php
namespace ArtSkills\Database\Type;

use Cake\Database\Driver;

class JsonType extends \Cake\Database\Type\JsonType
{

	/** @inheritdoc */
	public function toPHP($value, Driver $driver) {
		if ($value === null) {
			return null;
		}
		return parent::toPHP($value, $driver);
	}

	/** @inheritdoc */
	public function toDatabase($value, Driver $driver) {
		if ($value === null) {
			return null;
		}
		return parent::toDatabase($value, $driver);
	}

	/** @inheritdoc */
	public function toStatement($value, Driver $driver) {
		if ($value === null) {
			return \PDO::PARAM_NULL;
		}
		return parent::toStatement($value, $driver);
	}

}
