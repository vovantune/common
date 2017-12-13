<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use ArtSkills\ORM\Entity;

/**
 * @property int $id
 * @property int $table_one_fk
 * @tableComment description qqq
 */
class TestTableFour extends Entity
{
	/** @inheritdoc */
	protected $_aliases = [
	];
}