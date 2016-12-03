<?php

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


class RouterFactory
{
	use Nette\StaticClass;

	/**
	 * @return Nette\Application\IRouter
	 */
	public static function createRouter()
	{
		$router = new RouteList;

        //default
        //$router[] = new Route('<presenters>/<action>[/<id>]', 'Homepage:default');

        $router[] = new Route('install/<presenter>/<action>/<id>/', array(
            'module' => 'Install',
            'presenter' => 'Install',
            'action' => 'default',
            'id' => null
        ));

        return $router;
	}

}
