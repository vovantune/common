<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use ArtSkills\ORM\Entity;
use ArtSkills\Lib\Arrays;

/**
 * some comments blabla
 *
 * @property int $id comment1
 * @property int $col_enum
 * @property \Cake\I18n\Time $col_time = 'CURRENT_TIMESTAMP' asdasd
 * @property string $oldField
 * @property string $notExists
 * @tableComment description govno
 * more comments blabla
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
     * @return int[]
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
