<?php

namespace App\AdminModule\Presenters;


use App\Model\ACL\Permission;
use App\Model\ACL\Resource;

class AclPresenter extends AdminBasePresenter
{
    public function startup()
    {
        parent::startup();

        if (!$this->user->isAllowed(Resource::ACL, Permission::MANAGE)) {
            $this->flashMessage('admin.common.access_denied', 'danger', 'lock');
            $this->redirect(":Web:Page:default");
        }
    }
}