<?php

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


class RouterFactory
{
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

        $router[] = new Route('index.php', 'Front:Homepage:default', Route::ONE_WAY);

        $router[] = new Route('api/<action>[/<id>][/<area>]', array(
            'module' => 'Api',
            'presenter' => 'Api',
            'action' => 'default',
            'id' => null,
            'area' => null
        ));

        $router[] = new Route('admin/acl/<presenter>/<action>[/<id>][/<area>]', array(
            'module' => 'Admin:ACL',
            'presenter' => 'Page', //TODO
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

        $router[] = new Route('admin/configuration/<presenter>/<action>[/<id>][/<area>]', array(
            'module' => 'Admin:Configuration',
            'presenter' => 'Page', //TODO
            'action' => 'default',
            'id' => null,
            'area' => null
        ));

        $router[] = new Route('admin/mailing/<presenter>/<action>[/<id>][/<area>]', array(
            'module' => 'Admin:Mailing',
            'presenter' => 'Page', //TODO
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

        $router[] = new Route('admin/user/<presenter>/<action>[/<id>][/<area>]', array(
            'module' => 'Admin:User',
            'presenter' => 'Block', //TODO
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

        $router[] = new Route('install/<presenter>/<action>/<id>/', array(
            'module' => 'Install',
            'presenter' => 'Install',
            'action' => 'default',
            'id' => null
        ));

        $router[] = new Route('login/', 'Auth:login'); //TODO
        $router[] = new Route('logout/', 'Auth:logout'); //TODO

        $router[] = new Route('[!<pageId [a-z-0-9]+>]', array( //TODO
            'module' => 'Front',
            'presenter' => 'Page',
            'action' => 'default',
//            'pageId' => array(
//                Route::FILTER_IN => callback($this->pageRepository, 'slugToId'),
//                Route::FILTER_OUT => callback($this->pageRepository, "idToSlug")
//            )
        ));

        $router[] = new Route('<presenter>/<action>[/<id>]', 'Front:Homepage:default');

        return $router;
	}
}
