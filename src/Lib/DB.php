<?php
declare(strict_types=1);

namespace ArtSkills\Lib;

use ArtSkills\Traits\Library;
use Cake\Database\Connection;
use Cake\Database\Statement\MysqlStatement;
use Cake\Database\StatementInterface;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;

/**
 * @SuppressWarnings(PHPMD.ShortClassName)
 */
class DB
{
    use Library;

    const CONNECTION_DEFAULT = 'default';
    const CONNECTION_TEST = 'test';

    /**
     * Дефолтный коннекшн
     *
     * @param string $name
     * @param bool $useAliases
     * @return Connection
     */
    public static function getConnection(string $name = self::CONNECTION_DEFAULT, bool $useAliases = true): Connection
    {
        return ConnectionManager::get($name, $useAliases); // @phpstan-ignore-line
    }

    /**
     * переподсоединиться, если отвалился
     *
     * @param string $connectionName
     * @return void
     */
    public static function restoreConnection(string $connectionName = self::CONNECTION_DEFAULT)
    {
        $connection = self::getConnection($connectionName);
        if (!$connection->isConnected()) {
            $connection->connect();
        }
    }

    /**
     * Выполнить запрос не через построитель
     *
     * @param string $sql
     * @param string $connectionName
     * @return MysqlStatement|StatementInterface
     */
    public static function customQuery(string $sql, string $connectionName = self::CONNECTION_DEFAULT): StatementInterface
    {
        return self::getConnection($connectionName)->execute($sql);
    }
}
