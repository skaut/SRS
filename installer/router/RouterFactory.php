<?php

declare(strict_types=1);

namespace App\Installer;

use Nette;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

class RouterFactory
{
    public function createRouter() : Nette\Application\IRouter
    {
        $router = new RouteList();

        $router[] = new Route('install/<presenter>/<action>[/<id>]', [
            'module' => 'Install',
            'presenter' => 'Default',
            'action' => 'default',
            'id' => null,
        ]);
        
        $router[] = new Route('<presenter>/<action>[/<id>]', [
            'module' => 'Install',
            'presenter' => 'Default',
            'action' => 'default',
            'id' => null,
        ], Route::ONE_WAY);

        return $router;
    }
}
