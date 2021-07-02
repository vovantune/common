<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use ArtSkills\ORM\Table;

/**
 * @method \TestApp\Model\Entity\TestTableTwo newEntity(array | null $data = null, array $options = [])
 * @method \TestApp\Model\Entity\TestTableTwo[] newEntities(array $data, array $options = [])
 * @method \TestApp\Model\Entity\TestTableTwo patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \TestApp\Model\Entity\TestTableTwo[] patchEntities($entities, array $data, array $options = [])
 * @method \TestApp\Model\Entity\TestTableTwo|false save(\TestApp\Model\Entity\TestTableTwo $entity, array | \ArrayAccess $options = [])
 * @method \TestApp\Model\Entity\TestTableTwo|false saveArr(array $saveData, \TestApp\Model\Entity\TestTableTwo | null $entity = null, array $options = [])
 * @method \TestApp\Model\Query\TestTableTwoQuery find(string $type = "all", array | \ArrayAccess $options = [])
 * @method \TestApp\Model\Entity\TestTableTwo get($primaryKey, array | \ArrayAccess $options = [])
 * @method \TestApp\Model\Entity\TestTableTwo|false getEntity(\TestApp\Model\Entity\TestTableTwo | int $entity, array | \ArrayAccess $options = [])
 * @method \TestApp\Model\Entity\TestTableTwo|null updateWithLock(\TestApp\Model\Query\TestTableTwoQuery | array $queryData, array $updateData)
 */
class TestTableTwoTable extends Table
{
    /**
     * @inheritDoc
     * @phpstan-ignore-next-line
     */
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('TestTableOne', ['foreignKey' => 'table_one_fk']);
    }
}
