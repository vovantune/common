<?php

namespace ArtSkills\ORM;

use Cake\Database\Expression\QueryExpression;
use Cake\Database\FunctionsBuilder;

class Query extends \Cake\ORM\Query
{

	const CONDITION_ALL = '1=1';

	const ORDER_ASC = 'ASC';
	const ORDER_DESC = 'DESC';

	/**
	 * Построитель функций
	 *
	 * @var FunctionsBuilder
	 */
	private static $_funcBuilder = null;

	/**
	 * Сортировка рандомом
	 *
	 * @param string $field
	 * @param bool $overwrite
	 * @return $this
	 */
	public function orderRand($field, $overwrite = false)
	{
		return $this->order([$field => 'RAND()'], $overwrite);
	}

	/**
	 * статичная версия func()
	 *
	 * @return FunctionsBuilder
	 */
	public static function funct()
	{
		if (empty(self::$_funcBuilder)) {
			self::$_funcBuilder = new FunctionsBuilder();
		}
		return self::$_funcBuilder;
	}

	/**
	 * статичная версия newExpr()
	 *
	 * @param null $rawExpression
	 * @return QueryExpression
	 */
	public static function expr($rawExpression = null)
	{
		$expression = new QueryExpression();
		if ($rawExpression !== null) {
			$expression->add($rawExpression);
		}
		return $expression;
	}

}