<?php
declare(strict_types=1);

namespace TestApp\Controller\Chema;

/**
 * Тестовый класс для проверки пустых свойств родителя
 *
 * @OA\Schema()
 */
class FillChild extends EmptyParent
{
    /**
     * Статус ответа
     * @OA\Property(
     *     enum = {"ok", "error"},
     *     default = "ok"
     *     )
     *
     * @var string
     */
    public string $status = 'ok';
}
