<?php

declare(strict_types=1);

namespace App\Router;

use App\Model\Cms\Dto\PageDto;
use App\Services\CmsService;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

final class RouterFactory
{
    public function __construct(private readonly CmsService $cmsService)
    {
    }

    public function createRouter(): RouteList
    {
        $router = new RouteList();

        $router->addRoute('index.php', 'Web:Page:default', Route::ONE_WAY);

        $router->addRoute('api/<presenter>/<action>[/<id>]', [
            'module' => 'Api',
            'presenter' => null,
            'action' => null,
            'id' => null,
        ]);

        $router->addRoute('export/<presenter>/<action>[/<id>]', [
            'module' => 'Export',
            'presenter' => null,
            'action' => null,
            'id' => null,
        ]);

        $router->addRoute('action/<presenter>/<action>[/<id>]', [
            'module' => 'Action',
            'presenter' => null,
            'action' => null,
            'id' => null,
        ]);

        $router->addRoute('admin/cms/<presenter>/<action>[/<id>][/<area>]', [
            'module' => 'Admin:Cms',
            'presenter' => 'Page',
            'action' => 'default',
            'id' => null,
            'area' => null,
        ]);

        $router->addRoute('admin/program/<presenter>/<action>[/<id>]', [
            'module' => 'Admin:Program',
            'presenter' => 'Block',
            'action' => 'default',
            'id' => null,
        ]);

        $router->addRoute('admin/payments/<presenter>/<action>[/<id>]', [
            'module' => 'Admin:Payments',
            'presenter' => 'Payments',
            'action' => 'default',
            'id' => null,
        ]);

        $router->addRoute('admin/mailing/<presenter>/<action>[/<id>]', [
            'module' => 'Admin:Mailing',
            'presenter' => 'Auto',
            'action' => 'default',
            'id' => null,
        ]);

        $router->addRoute('admin/configuration/<presenter>/<action>[/<id>]', [
            'module' => 'Admin:Configuration',
            'presenter' => 'Seminar',
            'action' => 'default',
            'id' => null,
        ]);

        $router->addRoute('admin/<presenter>/<action>[/<id>]', [
            'module' => 'Admin',
            'presenter' => 'Dashboard',
            'action' => 'default',
            'id' => null,
        ]);

        $router->addRoute('install/<action>/<id>/', [
            'module' => 'Install',
            'presenter' => 'Install',
            'action' => 'default',
            'id' => null,
        ]);

        $router->addRoute('login/', 'Auth:login');
        $router->addRoute('logout/', 'Auth:logout');

        try {
            $router->addRoute('[page/<slug>/<action>]', [
                'module' => 'Web',
                'presenter' => 'Page',
                'page' => [
                    Route::FILTER_IN => fn (string $slug) => $this->cmsService->findPublishedBySlugDto($slug),
                    Route::FILTER_OUT => static fn (PageDto $page) => $page->getSlug(),
                ],
                'action' => 'default',
            ]);
        } catch (TableNotFoundException) {
        }

        $router->addRoute('<presenter>/<action>[/<id>]', [
            'module' => 'Web',
            'presenter' => 'Page',
            'action' => 'default',
            'id' => null,
        ]);

        return $router;
    }
}
