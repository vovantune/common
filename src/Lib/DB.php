<?php

namespace ArtSkills\Lib;

use ArtSkills\Traits\Library;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;

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
	public static function getConnection($name = self::CONNECTION_DEFAULT, $useAliases = true)
	{
		return ConnectionManager::get($name, $useAliases);
	}

	/**
	 * переподсоединиться, если отвалился
	 *
	 * @param string $connectionName
	 */
	public static function restoreConnection($connectionName = self::CONNECTION_DEFAULT)
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
	 * @return \Cake\Database\Statement\MysqlStatement
	 */
	public static function customQuery($sql, $connectionName = self::CONNECTION_DEFAULT)
	{
		return self::getConnection($connectionName)->execute($sql);
	}

}