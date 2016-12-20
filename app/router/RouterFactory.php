<?php

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


class RouterFactory
{
    /**
     * @var \App\Model\CMS\PageRepository
     */
    private $pageRepository;

    public function __construct(\App\Model\CMS\PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;
    }

    /**
     * @return Nette\Application\IRouter
     */
    public function createRouter()
    {
        $router = new RouteList;

        $router[] = new Route('index.php', 'Web:Page:default', Route::ONE_WAY);

        $router[] = new Route('api/<action>[/<id>][/<area>]', array(
            'module' => 'Api',
            'presenter' => 'Api',
            'action' => 'default',
            'id' => null,
            'area' => null
        ));

        $router[] = new Route('admin/cms/<presenter>/<action>[/<id>][/<area>]', array(
            'module' => 'Admin:CMS',
            'presenter' => 'Page',
            'action' => 'default',
            'id' => null,
            'area' => null
        ));

        $router[] = new Route('admin/program/<presenter>/<action>[/<id>][/<area>]', array(
            'module' => 'Admin:Program',
            'presenter' => 'Block',
            'action' => 'list',
            'id' => null,
            'area' => null
        ));

        $router[] = new Route('admin/<presenter>/<action>[/<id>][/<area>]', array(
            'module' => 'Admin',
            'presenter' => 'Dashboard',
            'action' => 'default',
            'id' => null,
            'area' => null
        ));

        $router[] = new Route('install/<action>/<id>/', array(
            'module' => 'Install',
            'presenter' => 'Install',
            'action' => 'default',
            'id' => null
        ));

        $router[] = new Route('login/', 'Auth:login');
        $router[] = new Route('logout/', 'Auth:logout');

        try {
            $this->pageRepository->findAll(); //vyvola vyjimku, pokud neexistuje databaze

            $router[] = new Route('[!<pageId [a-z-0-9]+>]', array(
                'module' => 'Web',
                'presenter' => 'Page',
                'action' => 'default',
                'pageId' => array(
                    Route::FILTER_IN => [$this->pageRepository, 'slugToId'],
                    Route::FILTER_OUT => [$this->pageRepository, "idToSlug"]
                )
            ));
        } catch (\Doctrine\DBAL\Exception\TableNotFoundException $ex) { }

        $router[] = new Route('<presenter>/<action>[/<id>]', 'Web:Page:default');

        return $router;
    }
}
