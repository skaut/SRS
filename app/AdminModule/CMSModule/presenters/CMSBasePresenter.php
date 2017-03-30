<?php

namespace App\AdminModule\CMSModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;


/**
 * BasePresenter pro CMSModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class CMSBasePresenter extends AdminBasePresenter
{
    protected $resource = Resource::CMS;


    public function startup()
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }

    public function beforeRender()
    {
        parent::beforeRender();

        $this->template->sidebarVisible = TRUE;
    }
}
