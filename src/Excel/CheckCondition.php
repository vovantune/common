<?php
declare(strict_types=1);

namespace ArtSkills\Excel;

use ArtSkills\Error\InternalException;

/**
 * Проверочное условие для соответствия документа
 */
class CheckCondition
{
    /**
     * @var int
     */
    public int $workCol;

    /**
     * @var int
     */
    public int $workRow;

    /**
     * @var string
     */
    public string $data;

    /**
     * Адрес ячейки
     * @var string
     */
    public string $address;

    /**
     * CheckCondition constructor.
     *
     * @param string $address A1
     * @param string $data
     * @throws InternalException
     */
    public function __construct(string $address, string $data)
    {
        if (empty($address)) {
            throw new InternalException("Адрес должен быть не пустым");
        }
        $this->address = $address;

        if (empty($data)) {
            throw new InternalException("Проверочное значение должно быть не пустым");
        }
        $this->data = $data;
    }

    /**
     * Создаём экземпляр
     *
     * @param string $address
     * @param string $data
     * @return CheckCondition
     * @throws InternalException
     */
    public static function instance(string $address, string $data): CheckCondition
    {
        return new CheckCondition($address, $data);
    }
}
