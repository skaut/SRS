<?php

namespace App;

use App\Model\CMS\PageRepository;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


class RouterFactory
{
    /**
     * @var PageRepository
     */
    private $pageRepository;

    public function __construct(PageRepository $pageRepository)
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

        $router[] = new Route('api/<presenter>/<action>[/<id>]', [
            'module' => 'Api',
            'presenter' => null,
            'action' => null,
            'id' => null,
            'area' => null
        ]);

        $router[] = new Route('admin/cms/<presenter>/<action>[/<id>][/<area>]', [
            'module' => 'Admin:CMS',
            'presenter' => 'Page',
            'action' => 'default',
            'id' => null,
            'area' => null
        ]);

        $router[] = new Route('admin/program/<presenter>/<action>[/<id>]', [
            'module' => 'Admin:Program',
            'presenter' => 'Block',
            'action' => 'default',
            'id' => null
        ]);

        $router[] = new Route('admin/mailing/<presenter>/<action>[/<id>]', [
            'module' => 'Admin:Mailing',
            'presenter' => 'Auto',
            'action' => 'default',
            'id' => null
        ]);

        $router[] = new Route('admin/configuration/<presenter>/<action>[/<id>]', [
            'module' => 'Admin:Configuration',
            'presenter' => 'Seminar',
            'action' => 'default',
            'id' => null
        ]);

        $router[] = new Route('admin/<presenter>/<action>[/<id>]', [
            'module' => 'Admin',
            'presenter' => 'Dashboard',
            'action' => 'default',
            'id' => null
        ]);

        $router[] = new Route('install/<action>/<id>/', [
            'module' => 'Install',
            'presenter' => 'Install',
            'action' => 'default',
            'id' => null
        ]);

        $router[] = new Route('login/', 'Auth:login');
        $router[] = new Route('logout/', 'Auth:logout');

        try {
            $router[] = new Route('[page/<slug>/<action>]', [
                'module' => 'Web',
                'presenter' => 'Page',
                'action' => 'default',
                'page' => [
                    Route::FILTER_IN => function ($page) {
                        return $this->pageRepository->findBySlug($page);
                    },
                    Route::FILTER_OUT => function ($page) {
                        return $page->getSlug();
                    }
                ]
            ]);
        } catch (TableNotFoundException $ex) { }

        $router[] = new Route('<presenter>/<action>[/<id>]', [
            'module' => 'Web',
            'presenter' => 'Page',
            'action' => 'default',
            'id' => null
        ]);

        return $router;
    }
}
