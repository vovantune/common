<?php
declare(strict_types=1);

namespace ArtSkills\Excel\Format;

use ArtSkills\Error\InternalException;
use ArtSkills\Error\UserException;
use Exception;

abstract class AbstractReaderFormat
{
    /**
     * AbstractReaderFormat constructor.
     *
     * @param string $fileName
     */
    abstract public function __construct(string $fileName);

    /**
     * Читаем содержимое ячейки.
     * Процесс не быстрый, поэтому использовать надо редко
     *
     * @param string $pCoordinate Координата поля
     * @param int $page Номер страницы
     * @return mixed|null
     * @throws UserException
     */
    abstract public function getCell(string $pCoordinate, int $page = 1);

    /**
     * Считываем содержимое страницы
     *
     * @param int $page Номер страницы
     * @param int $dataRowIndex Номер строки
     * @param bool $skipEmptyRows Пропускать пустые строки?
     * @return array<int, array<int, string>>|null
     */
    abstract public function getRows(int $page = 1, int $dataRowIndex = 2, bool $skipEmptyRows = true): ?array;

    /**
     * Получаем экземпляр класса исходя из расширения файла
     *
     * @param string $filename
     * @return AbstractReaderFormat
     * @throws Exception
     */
    public static function getInstance(string $filename): AbstractReaderFormat
    {
        if (!file_exists($filename)) {
            throw new InternalException("Файл $filename не существует!");
        }
        return new DefaultReaderFormat($filename);
    }
}
