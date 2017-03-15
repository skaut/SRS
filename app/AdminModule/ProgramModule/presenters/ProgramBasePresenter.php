<?php

namespace App\AdminModule\ProgramModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;


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

        $this->template->sidebarVisible = true;
    }
}