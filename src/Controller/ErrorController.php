<?php

namespace ArtSkills\Controller;

use ArtSkills\Lib\Arrays;
use Cake\Event\Event;

/**
 * Часть методов скопирована из Cake\Controller\ErrorController,
 * потому что хотелось отнаследоваться от нашего Controller
 */
class ErrorController extends Controller
{

	/** Скопировано из Cake\Controller\ErrorController */
	public function initialize() {
		$this->loadComponent('RequestHandler');
	}

	/**
	 * Скопировано из Cake\Controller\ErrorController
	 * Дописал отсылку ошибок в нашем стиле
	 *
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function beforeRender(Event $event) {
		$this->viewBuilder()->setTemplatePath('Error');
		if ($this->_isJsonAction()) {
			$serializedVars = Arrays::get($this->viewVars, '_serialize', ['message']);
			$serializedVars[] = 'status';
			$this->_sendJsonError($this->viewVars['message'], $this->viewVars);
			$this->set('_serialize', $serializedVars);
		}
	}

}