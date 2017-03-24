<?php
use Cake\Core\Plugin;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;


Router::defaultRouteClass(DashedRoute::class);
Plugin::routes();
