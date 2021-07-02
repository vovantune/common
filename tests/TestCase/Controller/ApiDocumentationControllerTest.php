<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\Controller;

use ArtSkills\TestSuite\ApiDocumentationTest;
use ArtSkills\TestSuite\AppControllerTestCase;

class ApiDocumentationControllerTest extends AppControllerTestCase
{
    /**
     * Тест API документации в JSON и в HTML формате. Может работать относительно долго, ибо строит апи по всему коду.
     */
    public function test(): void
    {
        (new ApiDocumentationTest())->testSchema($this->getJsonResponse('/apiDocumentation.json'));
    }
}
