<?php

namespace ArtSkills\Test\TestCase\ORM;

use ArtSkills\TestSuite\AppTestCase;
use TestApp\Model\Table\TestTableOneTable;
use TestApp\Model\Table\TestTableTwoTable;

/**
 * @property TestTableOneTable $TestTableOne
 * @property TestTableTwoTable $TestTableTwo
 */
class EntityTest extends AppTestCase
{

	/** @inheritdoc */
	public $fixtures = [
		'app.test_table_one',
		'app.test_table_two',
	];

	/** тест работы алиасов */
	public function testAliases()
	{
		$entityId = 88;
		$entity = $this->TestTableTwo->get($entityId);

		// простой доступ к полям
		self::assertEquals($entity->table_one_fk, $entity->fieldAlias);
		self::assertEquals($entity->get('table_one_fk'), $entity->get('fieldAlias'));

		$oldValue = $entity->table_one_fk;

		// при присвоении алиасу обновляется реальное значение
		$newValue = 54;
		$entity->fieldAlias = $newValue;
		self::assertEquals($newValue, $entity->table_one_fk);

		// при использовании set над алиасом обновляется реальное значение
		$newValue++;
		$entity->set('fieldAlias', $newValue);
		self::assertEquals($newValue, $entity->table_one_fk);

		// при использовании set с массивом над алиасом обновляется реальное значение
		$newValue++;
		$entity->set(['fieldAlias' => $newValue]);
		self::assertEquals($newValue, $entity->table_one_fk);

		// при использовании patchEntity над алиасом обновляется реальное значение
		$newValue++;
		$entity = $this->TestTableTwo->patchEntity($entity, ['fieldAlias' => $newValue]);
		self::assertEquals($newValue, $entity->table_one_fk);

		// при присвоении реальному значению обновляется алиас
		$newValue++;
		$entity->table_one_fk = $newValue;
		self::assertEquals($newValue, $entity->fieldAlias);

		// работает getOriginal
		self::assertEquals($oldValue, $entity->getOriginal('fieldAlias'));

		// алиасы попадают в массив
		$expectedArray = [
			'id' => $entityId,
			'table_one_fk' => $newValue,
			'fieldAlias' => $newValue,
			'col_text' => null,
		];
		self::assertEquals($expectedArray, $entity->toArray());

		// при использовании newEntity с алиасом обновляется реальное значение
		$newValue++;
		$newEntity = $this->TestTableTwo->newEntity(['fieldAlias' => $newValue]);
		self::assertEquals($newValue, $newEntity->table_one_fk);
	}

	/** проверка на изменение значения поля */
	public function testChanged()
	{
		$entity = $this->TestTableTwo->get(88);
		$entity->table_one_fk = $entity->table_one_fk;
		self::assertTrue($entity->isDirty('table_one_fk'));
		self::assertFalse($entity->changed('table_one_fk'));
	}

	/** удаление дочерней сущности */
	public function testDeleteChild()
	{
		$assocName = 'TestTableTwo';
		$childIds = [88, 90];

		$entity = $this->TestTableOne->get(45, ['contain' => $assocName]);
		self::assertEquals($childIds, array_column($entity->toArray()[$assocName], 'id'));

		$childIndex = 0;
		$entity->deleteChild($assocName, $childIndex);
		unset($childIds[$childIndex]);
		self::assertEquals(array_values($childIds), array_column($entity->toArray()[$assocName], 'id'));
		self::assertTrue($entity->isDirty($assocName));
	}

	/**
	 * удаление несуществующей дочерней сущности
	 */
	public function testDeleteChildNotExists()
	{
		$this->expectExceptionMessage("Unknown property TestTableTwo");
		$this->expectException(\Exception::class);
		$entity = $this->TestTableOne->get(45);
		$entity->deleteChild('TestTableTwo', 0);
	}


}
