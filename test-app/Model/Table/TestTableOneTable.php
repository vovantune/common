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
 * @method string save() bad declaration
 * bla bla more comments
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