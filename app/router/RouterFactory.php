<?php

declare(strict_types=1);

namespace App;

use App\Model\CMS\PageDTO;
use App\Services\CMSService;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Nette;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

class RouterFactory
{
    /** @var CMSService */
    private $CMSService;


    public function __construct(CMSService $CMSService)
    {
        $this->CMSService = $CMSService;
    }

    public function createRouter() : Nette\Application\IRouter
    {
        $router = new RouteList();

        $router[] = new Route('index.php', 'Web:Page:default', Route::ONE_WAY);

        $router[] = new Route('api/<presenter>/<action>[/<id>]', [
            'module' => 'Api',
            'presenter' => null,
            'action' => null,
            'id' => null,
        ]);

        $router[] = new Route('export/<presenter>/<action>[/<id>]', [
            'module' => 'Export',
            'presenter' => null,
            'action' => null,
            'id' => null,
        ]);

        $router[] = new Route('action/<presenter>/<action>[/<id>]', [
            'module' => 'Action',
            'presenter' => null,
            'action' => null,
            'id' => null,
        ]);

        $router[] = new Route('admin/cms/<presenter>/<action>[/<id>][/<area>]', [
            'module' => 'Admin:CMS',
            'presenter' => 'Page',
            'action' => 'default',
            'id' => null,
            'area' => null,
        ]);

        $router[] = new Route('admin/program/<presenter>/<action>[/<id>]', [
            'module' => 'Admin:Program',
            'presenter' => 'Block',
            'action' => 'default',
            'id' => null,
        ]);

        $router[] = new Route('admin/payments/<presenter>/<action>[/<id>]', [
            'module' => 'Admin:Payments',
            'presenter' => 'Payments',
            'action' => 'default',
            'id' => null,
        ]);

        $router[] = new Route('admin/mailing/<presenter>/<action>[/<id>]', [
            'module' => 'Admin:Mailing',
            'presenter' => 'Auto',
            'action' => 'default',
            'id' => null,
        ]);

        $router[] = new Route('admin/configuration/<presenter>/<action>[/<id>]', [
            'module' => 'Admin:Configuration',
            'presenter' => 'Seminar',
            'action' => 'default',
            'id' => null,
        ]);

        $router[] = new Route('admin/<presenter>/<action>[/<id>]', [
            'module' => 'Admin',
            'presenter' => 'Dashboard',
            'action' => 'default',
            'id' => null,
        ]);

        $router[] = new Route('install/<action>/<id>/', [
            'module' => 'Install',
            'presenter' => 'Install',
            'action' => 'default',
            'id' => null,
        ]);

        $router[] = new Route('login/', 'Auth:login');
        $router[] = new Route('logout/', 'Auth:logout');

        try {
            $router[] = new Route('[page/<slug>/<action>]', [
                'module' => 'Web',
                'presenter' => 'Page',
                'page' => [
                    Route::FILTER_IN => function (string $slug) {
                        return $this->CMSService->findPublishedBySlugDTO($slug);
                    },
                    Route::FILTER_OUT => function (PageDTO $page) {
                        return $page->getSlug();
                    },
                ],
                'action' => 'default',
            ]);
        } catch (TableNotFoundException $ex) {
        }

        $router[] = new Route('<presenter>/<action>[/<id>]', [
            'module' => 'Web',
            'presenter' => 'Page',
            'action' => 'default',
            'id' => null,
        ]);

        return $router;
    }
}
