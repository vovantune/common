<?php
declare(strict_types=1);

namespace ArtSkills\Controller;

use ArtSkills\Lib\Git;
use ArtSkills\Shell\DeploymentShell;
use Cake\Http\Response;

class GitController extends Controller
{
    /**
     * Обновление кода на сайте при пуше в мастера
     * + сброс кеша и увеличение счётчика версий скриптов
     */
    public function update(): ?Response
    {
        $this->viewBuilder()->setLayout(false);
        $payload = Git::parseGithubRequest($this->request->getData());
        DeploymentShell::deployInBg(DeploymentShell::TYPE_PRODUCTION, $payload['repo'], $payload['branch']);
        opcache_reset();
        return $this->_sendTextResponse('');
    }
}
