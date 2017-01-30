<?php

namespace App\AdminModule\MailingModule\Presenters;


use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;

abstract class MailingBasePresenter extends AdminBasePresenter
{
    public function startup()
    {
        parent::startup();

        if (!$this->user->isAllowed(Resource::MAILING, Permission::MANAGE)) {
            $this->flashMessage('admin.common.access_denied', 'danger', 'lock');
            $this->redirect(":Web:Page:default");
        }
    }
}