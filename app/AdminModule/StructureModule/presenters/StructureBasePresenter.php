<?php

namespace App\AdminModule\StructureModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;


/**
 * Basepresenter pro StructureModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class StructureBasePresenter extends AdminBasePresenter
{
    protected $resource = Resource::STRUCTURE;


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
