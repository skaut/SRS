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

    protected $resource = Resource::USERS;

    public function startup()
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }

    public function renderList() {

    }

    protected function createComponentUsersGrid($name)
    {
        return $this->usersGridControlFactory->create();
    }

}