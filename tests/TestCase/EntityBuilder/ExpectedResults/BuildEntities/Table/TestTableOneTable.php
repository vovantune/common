<?php
namespace TestApp\Model\Table;

use ArtSkills\ORM\Table;
use ArtSkills\Lib\Arrays;

/**
 * bla bla old comments
 * @method \TestApp\Model\Entity\TestTableOne newEntity(array|null $data = null, array $options = [])
 * @method \TestApp\Model\Entity\TestTableOne[] newEntities(array $data, array $options = [])
 * @method \TestApp\Model\Entity\TestTableOne patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \TestApp\Model\Entity\TestTableOne[] patchEntities($entities, array $data, array $options = [])
 * @method \TestApp\Model\Entity\TestTableOne|false save(\TestApp\Model\Entity\TestTableOne $entity, array|\ArrayAccess $options = [])
 * bla bla more comments
 * @method \TestApp\Model\Entity\TestTableOne|false saveArr(array $saveData, \TestApp\Model\Entity\TestTableOne|null $entity = null, array $options = [])
 * @method \TestApp\Model\Query\TestTableOneQuery find(string $type = "all", array|\ArrayAccess $options = [])
 * @method \TestApp\Model\Entity\TestTableOne get($primaryKey, array|\ArrayAccess $options = [])
 * @method \TestApp\Model\Entity\TestTableOne|false getEntity(\TestApp\Model\Entity\TestTableOne|int $entity, array|\ArrayAccess $options = [])
 * @method \TestApp\Model\Entity\TestTableOne|null updateWithLock(\TestApp\Model\Query\TestTableOneQuery|array $queryData, array $updateData)
 * @method \TestApp\Model\Entity\TestTableOne touch(\TestApp\Model\Entity\TestTableOne $entity, string $eventName = 'Model.beforeSave')
 */
class TestTableOneTable extends Table
{

	public $asd;
	public function qwe() {
		return Arrays::encode(['asd' => 'qwe']);
	}

	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('TestTableTwo', ['foreignKey' => 'table_one_fk']);
		$this->addBehavior('Timestamp', [
			'events' => [
				'Model.beforeSave' => [
					'col_time' => 'always',
				],
			],
		]);
	}
}