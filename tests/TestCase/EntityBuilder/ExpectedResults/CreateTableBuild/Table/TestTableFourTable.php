<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use ArtSkills\ORM\Table;

/**
 * @method \TestApp\Model\Entity\TestTableFour newEntity(array | null $data = null, array $options = [])
 * @method \TestApp\Model\Entity\TestTableFour[] newEntities(array $data, array $options = [])
 * @method \TestApp\Model\Entity\TestTableFour patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \TestApp\Model\Entity\TestTableFour[] patchEntities($entities, array $data, array $options = [])
 * @method \TestApp\Model\Entity\TestTableFour|false save(\TestApp\Model\Entity\TestTableFour $entity, array | \ArrayAccess $options = null)
 * @method \TestApp\Model\Entity\TestTableFour|false saveArr(array $saveData, \TestApp\Model\Entity\TestTableFour | null $entity = null, array $options = [])
 * @method \TestApp\Model\Query\TestTableFourQuery find(string $type = "all", array | \ArrayAccess $options = null)
 * @method \TestApp\Model\Entity\TestTableFour get($primaryKey, array | \ArrayAccess $options = null)
 * @method \TestApp\Model\Entity\TestTableFour|false getEntity(\TestApp\Model\Entity\TestTableFour | int $entity, array | \ArrayAccess $options = null)
 * @method \TestApp\Model\Entity\TestTableFour|null updateWithLock(\TestApp\Model\Query\TestTableFourQuery | array $queryData, array $updateData)
 */
class TestTableFourTable extends Table
{

}