<?php
declare(strict_types=1);

namespace ArtSkills\Controller\Response;

use ArtSkills\ValueObject\ValueObject;

/**
 * Class ApiResponse
 *
 * @OA\Schema()
 */
class ApiResponse extends ValueObject
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

    /**
     * Описание ошибки в случае status = "error"
     * @OA\Property()
     *
     * @var string|null
     */
    public ?string $message = null;
}
