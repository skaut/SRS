<?php

declare(strict_types=1);

namespace App\Router;

use App\Model\Cms\PageDto;
use App\Services\CmsService;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

final class RouterFactory
{
    /** @var CmsService */
    private $cmsService;

    public function __construct(CmsService $cmsService)
    {
        $this->cmsService = $cmsService;
    }

    public function createRouter() : RouteList
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
            'module' => 'Admin:Cms',
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
                        return $this->cmsService->findPublishedBySlugDto($slug);
                    },
                    Route::FILTER_OUT => static function (PageDto $page) {
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
