<?php

namespace ArtSkills\Controller;

use ArtSkills\Error\Exception;
use ArtSkills\Lib\Arrays;
use Cake\Event\Event;

/**
 * Часть методов скопирована из Cake\Controller\ErrorController,
 * потому что хотелось отнаследоваться от нашего Controller
 */
class ErrorController extends Controller
{

    /** Скопировано из Cake\Controller\ErrorController */
    public function initialize()
    {
        $this->loadComponent('RequestHandler', [
            'enableBeforeRedirect' => false,
        ]);
    }

    /**
     * Скопировано из Cake\Controller\ErrorController
     * Дописал отсылку ошибок в нашем стиле
     *
     * @param Event $event
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeRender(Event $event)
    {
        $this->viewBuilder()->setTemplatePath('Error');
        if ($this->_isJsonAction()) {
            $serializedVars = Arrays::get($this->viewVars, '_serialize', ['message']);
            $serializedVars[] = 'status';
            $exception = Arrays::get($this->viewVars, 'error');
            if (($exception instanceof Exception) && array_key_exists('file', $this->viewVars)) {
                $this->viewVars = (array)$exception->getActualThrowSpot() + $this->viewVars;
            }
            $this->_sendJsonError($this->viewVars['message'], $this->viewVars);
            $this->set('_serialize', $serializedVars);
        }
    }
}
