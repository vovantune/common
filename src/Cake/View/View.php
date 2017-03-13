<?php
namespace ArtSkills\Cake\View;


/**
 * App View class
 *
 * @property \ArtSkills\Cake\View\Helper\AssetHelper $Asset
 */
class View extends \Cake\View\View
{

    /** @inheritdoc */
    public function initialize() {
        parent::initialize();
        $this->loadHelper('Asset');
    }
}
