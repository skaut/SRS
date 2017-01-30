<?php

namespace App\AdminModule\CMSModule\Presenters;


use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;

abstract class CMSBasePresenter extends AdminBasePresenter
{
    public function startup()
    {
        parent::startup();

        if (!$this->user->isAllowed(Resource::CMS, Permission::MANAGE)) {
            $this->flashMessage('admin.common.access_denied', 'danger', 'lock');
            $this->redirect(":Web:Page:default");
        }
    }

    public function beforeRender()
    {
        parent::beforeRender();

        $this->template->sidebarVisible = true;
    }

}