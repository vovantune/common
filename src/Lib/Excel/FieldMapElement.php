<?php
declare(strict_types=1);

namespace ArtSkills\Lib\Excel;

use ArtSkills\Error\InternalException;

/**
 * Элемент описания таблицы типов
 */
class FieldMapElement
{
    /** @var string Тип: Целое число */
    public const TYPE_INT = 'integer';
    /** @var string Тип: Десятичная дробь */
    public const TYPE_FLOAT = '#,##0.00';
    /** @var string Тип: Строка */
    public const TYPE_STRING = 'string';
    /** @var string Тип: Денежный */
    public const TYPE_MONEY = '#,##0.00 ₽';
    /** @var string Тип: Формула */
    public const TYPE_FORMULA = '@';

    /** @var string Типы данных */
    public const TYPES = [
        self::TYPE_INT,
        self::TYPE_FLOAT,
        self::TYPE_STRING,
        self::TYPE_MONEY,
        self::TYPE_FORMULA,
    ];

    /**
     * @var string
     */
    public string $name;

    /**
     * @var int
     */
    public int $workIndex;

    /**
     * @var string
     */
    public string $type;

    /**
     * Значение по-умолчанию
     *
     * @var mixed|null
     */
    public $defaultValue;

    /**
     * Автоматом переводить в нижний регистр результат строки
     *
     * @var bool
     */
    public bool $stringToLowerCase;

    /**
     * FieldMapElement constructor.
     *
     * @param string $name
     * @param int $index Нумерация с 1
     * @param string $type FieldMapElement::TYPES
     * @param mixed $defaultValue
     * @param bool $stringToLowerCase
     * @throws InternalException
     */
    public function __construct(string $name, int $index, string $type = self::TYPE_STRING, $defaultValue = null, bool $stringToLowerCase = false)
    {
        $this->name = $name;

        if ($index <= 0) {
            throw new InternalException("Некорректный параметр index:" . $index);
        }
        $this->workIndex = $index - 1;

        if (!in_array($type, self::TYPES, true)) {
            throw new InternalException("Некорректный параметр type:" . $type);
        }
        $this->type = $type;

        $this->defaultValue = $defaultValue;
        $this->stringToLowerCase = $stringToLowerCase;
    }

    /**
     * Создаем экземпляр
     *
     * @param string $name
     * @param int $index
     * @param string $type
     * @param mixed $defaultValue
     * @param bool $stringToLowerCase Переводить ли строку в нижний регистр
     * @return FieldMapElement
     * @throws InternalException
     */
    public static function create(string $name, int $index, string $type = self::TYPE_STRING, $defaultValue = null, bool $stringToLowerCase = false): FieldMapElement
    {
        return new FieldMapElement($name, $index, $type, $defaultValue, $stringToLowerCase);
    }
}
