<?php

namespace App\AdminModule\ProgramModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;


/**
 * Basepresenter pro ProgramModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class ProgramBasePresenter extends AdminBasePresenter
{
    protected $resource = Resource::PROGRAM;


    public function startup()
    {
        parent::startup();

        $this->checkPermission(Permission::ACCESS);
    }

    public function beforeRender()
    {
        parent::beforeRender();

        $this->template->sidebarVisible = TRUE;
    }
}
