<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use ArtSkills\ORM\Entity;
use ArtSkills\Lib\Arrays;

/**
 * some comments blabla
 *
 * @property int $id comment1
 * more comments blabla
 * @property string $col_enum = 'val1'
 * @property string $col_text
 * @property \Cake\I18n\Time $col_time = 'CURRENT_TIMESTAMP' comment2
 * @property ?TestTableTwo[] $TestTableTwo `table_one_fk` => `id`
 * @property array $newField
 * @property int $oldField
 * @tableComment description blabla
 */
class TestTableOne extends Entity
{
    /**
     * @return string
     */
    public function asd()
    {
        return Arrays::encode(['asd' => 'qwe']);
    }

    /**
     * @return array
     */
    protected function _getNewField()
    {
        return [];
    }

    /**
     * @return int поле изменилось
     */
    protected function _getOldField()
    {
        return 123;
    }

    /**
     * @return object кривое описание
     */
    protected function _getId()
    {
        return empty($this->_properties['id']) ? null : $this->_properties['id'];
    }
}
