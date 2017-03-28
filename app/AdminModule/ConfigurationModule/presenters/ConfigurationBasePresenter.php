<?php

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;


/**
 * Basepresenter pro ConfigurationModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class ConfigurationBasePresenter extends AdminBasePresenter
{
    protected $resource = Resource::CONFIGURATION;


    public function startup()
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }

    public function beforeRender()
    {
        parent::beforeRender();

        $this->template->sidebarVisible = true;
    }
}