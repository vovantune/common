<?php
declare(strict_types=1);

namespace ArtSkills\Shell;

use ArtSkills\EntityBuilder\EntityBuilder;
use ArtSkills\EntityBuilder\EntityBuilderConfig;
use ArtSkills\EntityBuilder\TableDocumentation;
use ArtSkills\Error\InternalException;
use ArtSkills\ORM\Entity;
use ArtSkills\ORM\Table;
use Cake\Console\Shell;

class EntityBuilderShell extends Shell
{
    /**
     * Формируем/обновляем сущности
     */
    public function main()
    {
        if ($this->_buildEntityAndDoc()) {
            $this->out('Has changes, update Model folder');
        }
    }

    /**
     * инициализация конфига
     */
    private function _setConfig()
    {
        $config = EntityBuilderConfig::create()
            ->setModelFolder(APP . 'Model')
            ->setBaseTableClass(Table::class)
            ->setBaseEntityClass(Entity::class);
        EntityBuilder::setConfig($config);
        TableDocumentation::setConfig($config);
    }

    /**
     * Создаём класс таблицы и сущности из существующей таблицы в базе
     */
    public function createFromDb()
    {
        $this->_setConfig();
        $newTableFile = EntityBuilder::createTableClass($this->args[0]);
        require_once $newTableFile;
        EntityBuilder::build();
        $this->out('Yahaa, update Model folder');
    }

    /**
     * Генерим сущности и документацию
     *
     * @return bool
     * @throws InternalException
     */
    private function _buildEntityAndDoc()
    {
        $this->_setConfig();
        $hasEntityChanges = EntityBuilder::build();
        $hasDocChanges = TableDocumentation::build();
        return $hasEntityChanges || $hasDocChanges;
    }

    /**
     * Добавление команд и их параметров
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->addSubcommand('createFromDb', [
            'help' => __('CREATE TABLE class AND Entity class FROM existance DB TABLE'),
            'parser' => [
                'arguments' => [
                    'tableName' => ['help' => __('Real table name'), 'required' => true],
                ],
            ],
        ]);
        return $parser;
    }
}
