<?php

namespace ArtSkills\Test\TestCase\ORM;

use ArtSkills\TestSuite\Mock\MethodMocker;
use ArtSkills\TestSuite\AppTestCase;
use Cake\Database\Driver;
use Cake\I18n\Time;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Query;
use Cake\ORM\Table;
use TestApp\Model\Entity\TestTableOne;
use TestApp\Model\Entity\TestTableTwo;
use TestApp\Model\Table\TestTableOneTable;
use TestApp\Model\Table\TestTableTwoTable;

/**
 * @property TestTableOneTable $TestTableOne
 * @property TestTableTwoTable $TestTableTwo
 */
class TableTest extends AppTestCase
{

    /**
     * @inheritdoc
     */
    public $fixtures = [
        'app.test_table_one',
        'app.test_table_two',
    ];

    /**
     * Получение сущности разными способами
     */
    public function testGetEntity()
    {
        self::assertInstanceOf(TestTableOneTable::class, TestTableOneTable::instance(), 'Не сработало получение инстанса');

        self::assertFalse($this->TestTableOne->getEntity(-1)); // не выкинулся ексепшн

        $testId = 45;
        $testEntity = $this->TestTableOne->getEntity($testId, ['contain' => 'TestTableTwo']);
        self::assertInstanceOf(TestTableOne::class, $testEntity, 'Не вернулась сущность');
        self::assertEquals($testId, $testEntity->id, 'Вернулась не та сущность');
        self::assertNotEmpty($testEntity->TestTableTwo, 'Не применились опции');
        self::assertSame($testEntity, $this->TestTableOne->getEntity($testEntity), 'Не вернулась сущность');
    }

    /**
     * Сохранение в одно действие
     */
    public function testSaveArr()
    {
        // сохранение новой записи
        $saveData = [
            'col_enum' => 'val2',
            'col_text' => 'textextext',
            'col_time' => '2017-03-17 16:34:44',
        ];

        $saveResult = $this->TestTableOne->saveArr($saveData);
        self::assertInstanceOf(TestTableOne::class, $saveResult, 'Неправильный результат сохранения');

        $expectedData = array_replace($saveData, [
            'col_time' => new Time($saveData['col_time']),
            'id' => $saveResult->id,
        ]);
        $newRecord = $this->TestTableOne->get($saveResult->id);
        $this->assertEntityEqualsArray($expectedData, $newRecord, 'Неправильно создалось');

        // редактирование
        $newText = '2222222222';
        $saveResult = $this->TestTableOne->saveArr([
            'col_text' => $newText,
            'col_time' => $saveData['col_time'],
        ], $newRecord, ['dirtyFields' => 'col_time']);
        self::assertInstanceOf(TestTableOne::class, $saveResult, 'Неправильный результат сохранения при редактировании');

        $expectedData['col_text'] = $newText;
        $newRecord = $this->TestTableOne->get($newRecord->id);
        $this->assertEntityEqualsArray($expectedData, $newRecord, 'Неправильно отредактировалось');

        // редактирование по id
        $newText = 'zzzzzzzz';
        $saveResult = $this->TestTableOne->saveArr([
            'col_text' => $newText,
            'col_time' => $saveData['col_time'],
        ], $newRecord->id, ['dirtyFields' => 'col_time']);
        self::assertInstanceOf(TestTableOne::class, $saveResult, 'Неправильный результат сохранения при редактировании по id');

        $expectedData['col_text'] = $newText;
        $newRecord = $this->TestTableOne->get($newRecord->id);
        $this->assertEntityEqualsArray($expectedData, $newRecord, 'Неправильно отредактировалось по id');
    }

    /**
     * Редактирование связанных сущностей
     */
    public function testChildEdit()
    {
        $testId = 45;
        $assoc = 'TestTableTwo';

        // если дочерняя сущность dirty, а родительская - нет, то дочерняя сохранится
        $newText = 'test text ololo';
        $testEntity = $this->TestTableOne->getEntity($testId, ['contain' => $assoc]);
        self::assertNotEquals($newText, $testEntity->TestTableTwo[0]->col_text);
        $testEntity->TestTableTwo[0]->col_text = $newText;
        $this->TestTableOne->save($testEntity);
        $testEntity = $this->TestTableOne->getEntity($testId, ['contain' => $assoc]);
        self::assertEquals($newText, $testEntity->TestTableTwo[0]->col_text);

        // смена способа сохранения дочерних сущностей
        $testEntity = $this->TestTableOne->getEntity($testId, ['contain' => $assoc]);
        self::assertEquals(HasMany::SAVE_APPEND, $this->TestTableOne->$assoc->getSaveStrategy());
        self::assertCount(2, $testEntity->TestTableTwo);
        $testEntity->deleteChild($assoc, 1);
        $this->TestTableOne->save($testEntity);
        // на самом деле не удалилась
        $testEntity = $this->TestTableOne->getEntity($testId, ['contain' => $assoc]);
        self::assertCount(2, $testEntity->TestTableTwo);

        // а теперь удалится
        $testEntity->deleteChild($assoc, 1);
        $this->TestTableOne->save($testEntity, ['assocStrategies' => [$assoc => HasMany::SAVE_REPLACE]]);
        // стратегия изменилась ровно на одно сохранение
        self::assertEquals(HasMany::SAVE_APPEND, $this->TestTableOne->$assoc->getSaveStrategy());
        $testEntity = $this->TestTableOne->getEntity($testId, ['contain' => $assoc]);
        self::assertCount(1, $testEntity->TestTableTwo);
    }

    /**
     * exists с contain
     */
    public function testExistsContain()
    {
        //существование записи
        $exists = $this->TestTableTwo->exists(['id' => 89]);
        self::assertTrue($exists, 'Не найдена запись');

        $notExists = $this->TestTableTwo->exists(['TestTableTwo.id' => 89], ['TestTableOne' => ['joinType' => 'INNER']]);
        self::assertFalse($notExists, 'Найдена запись, хотя не должна');
    }

    /**
     * Попытка вставить запись с плохим внешним ключом
     */
    public function testBadFK()
    {
        $this->expectExceptionMessage("a foreign key constraint fails");
        $this->expectException(\PDOException::class);
        $this->TestTableTwo->saveArr(['table_one_fk' => 88]);
    }

    /**
     * Получаем запись с блокировкой
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testFindAndLock()
    {
        MethodMocker::sniff(Table::class, 'save')->expectCall(1);
        MethodMocker::sniff(Query::class, 'sql', function ($args, $result) {
            static $firstQuery = true;
            if ($firstQuery) {
                self::assertContains('LIMIT 1 FOR UPDATE', $result, 'Не добавилась блокировка запроса');
                $firstQuery = false;
            }
        });

        MethodMocker::sniff(Driver::class, 'beginTransaction', function () {
            self::assertTrue(true, 'Транзакция не началась');
        });

        MethodMocker::sniff(Driver::class, 'commitTransaction', function () {
            self::assertTrue(true, 'Транзакция не закончилась');
        });

        $query = $this->TestTableTwo->find()->where(['id' => 89]);

        $newValue = 45;
        $result = $this->TestTableTwo->updateWithLock($query, ['table_one_fk' => $newValue]);

        self::assertInstanceOf(TestTableTwo::class, $result);
        self::assertEquals($newValue, $result->table_one_fk, 'Не сработало обновление');
    }

    /**
     * Поиск записи с блокировкой при пустом результате
     */
    public function testFindAndLockEmpty()
    {
        MethodMocker::sniff(Table::class, 'save')->expectCall(0);
        $query = $this->TestTableTwo->find()->where(['id' => 26]);
        $result = $this->TestTableTwo->updateWithLock($query, ['table_one_fk' => 45]);
        self::assertNull($result, 'Не пустой результат при некорректном запросе');
    }

    /**
     * короткое описание опций для findList
     */
    public function testShortFindList()
    {
        // одно поле - и ключ и значение
        $classicList = $this->TestTableTwo->find('list', [
            'keyField' => 'id',
            'valueField' => 'id',
        ])->toArray();
        $shortList = $this->TestTableTwo->find('list', ['id'])->toArray();
        $expectedList = [
            88 => 88,
            89 => 89,
            90 => 90,
        ];
        self::assertEquals($expectedList, $classicList);
        self::assertEquals($expectedList, $shortList);

        // ключ => значение
        $classicList = $this->TestTableTwo->find('list', [
            'keyField' => 'id',
            'valueField' => 'table_one_fk',
        ])->toArray();
        $shortList = $this->TestTableTwo->find('list', ['id' => 'table_one_fk'])->toArray();
        $expectedList = [
            88 => 45,
            89 => 89,
            90 => 45,
        ];
        self::assertEquals($expectedList, $classicList);
        self::assertEquals($expectedList, $shortList);

        // выражения и алиасы
        $query = $this->TestTableTwo->find();
        $classicList = $this->TestTableTwo
            ->find('list', [
                'keyField' => 'table_one_fk',
                'valueField' => 'cnt',
            ])
            ->select([
                'table_one_fk',
                'cnt' => $query->func()->count('*'),
            ])
            ->group(['table_one_fk'])
            ->toArray();
        $shortList = $this->TestTableTwo->find('list', ['table_one_fk' => 'cnt'])
            ->select([
                'table_one_fk',
                'cnt' => $query->func()->count('*'),
            ], true)
            ->group(['table_one_fk'])
            ->toArray();
        $expectedList = [
            45 => 2,
            89 => 1,
        ];
        self::assertEquals($expectedList, $classicList);
        self::assertEquals($expectedList, $shortList);

        // сортировка
        $shortList = $this->TestTableTwo->find('list', ['table_one_fk'])
            ->select([
                'cnt' => $query->func()->count('*'),
            ])
            ->group(['table_one_fk'])
            ->orderAsc('cnt')
            ->toArray();
        $expectedList = [
            89 => 89,
            45 => 45,
        ];
        self::assertEquals($expectedList, $shortList);
        // проверка сортировки. просто assertEquals не учитывает порядок ключей
        self::assertEquals(array_keys($expectedList), array_keys($shortList));

        // джоины
        $shortList = $this->TestTableTwo
            ->find('list', [
                'id' => 'TestTableOne.col_text',
            ])
            ->contain('TestTableOne')
            ->where([
                'table_one_fk' => 45,
            ])
            ->toArray();
        $expectedList = [
            88 => 'olololo',
            90 => 'olololo',
        ];
        self::assertEquals($expectedList, $shortList);
    }
}
