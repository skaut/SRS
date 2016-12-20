<?php

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


class RouterFactory
{
    /**
     * @var \Kdyby\Doctrine\EntityManager
     */
	private $em;

    /**
     * @var \App\Services\ConfigFacade
     */
	private $configFacade;

    public function __construct(\Kdyby\Doctrine\EntityManager $em, \App\Services\ConfigFacade $configFacade)
    {
        $this->em = $em;
        $this->configFacade = $configFacade;
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

        $config = $this->configFacade->loadConfig();
        if ($config['parameters']['installed']['connection'] && $config['parameters']['installed']['schema']) {
            $pageRepository = $this->em->getRepository(\App\Model\CMS\Page::class);

            $router[] = new Route('[!<pageId [a-z-0-9]+>]', array(
                'module' => 'Web',
                'presenter' => 'Page',
                'action' => 'default',
                'pageId' => array(
                    Route::FILTER_IN => [$pageRepository, 'slugToId'],
                    Route::FILTER_OUT => [$pageRepository, "idToSlug"]
                )
            ));
        }

        $router[] = new Route('<presenter>/<action>[/<id>]', 'Web:Page:default');

        return $router;
	}
}
