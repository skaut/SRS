<?php

namespace App\AdminModule\Presenters;


use App\AdminModule\Components\IUsersGridControlFactory;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;

class UsersPresenter extends AdminBasePresenter
{
    /**
     * @var IUsersGridControlFactory
     * @inject
     */
    public $usersGridControlFactory;

    public function startup()
    {
        parent::startup();

        if (!$this->user->isAllowed(Resource::USERS, Permission::MANAGE)) {
            $this->flashMessage('admin.common.access_denied', 'danger', 'lock');
            $this->redirect(":Web:Page:default");
        }
    }

    public function renderList() {

    }

    public function createComponentUsersGrid($name)
    {
        return $this->usersGridControlFactory->create($name);
    }

}