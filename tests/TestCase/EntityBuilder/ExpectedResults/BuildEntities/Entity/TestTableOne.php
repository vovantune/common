<?php
namespace TestApp\Model\Entity;

use ArtSkills\ORM\Entity;
use ArtSkills\Lib\Arrays;

/**
 * some comments blabla
 * @property int $id comment1
 * more comments blabla
 * @property string $col_enum = 'val1'
 * @property string $col_text
 * @property \Cake\I18n\Time $col_time = 'CURRENT_TIMESTAMP' comment2
 * @property TestTableTwo[] $TestTableTwo `table_one_fk` => `id`
 * @tableComment description blabla
 */
class TestTableOne extends Entity
{
	/** @inheritdoc */
	protected $_aliases = [
	];

	public function asd() {
		return Arrays::encode(['asd' => 'qwe']);
	}
}