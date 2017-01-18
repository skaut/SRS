<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Components\IUsersGridControlFactory;
use App\AdminModule\Components\UsersGridControl;
use App\AdminModule\Presenters\AdminBasePresenter;

class UsersPresenter extends AdminBasePresenter
{
    /**
     * @var IUsersGridControlFactory
     * @inject
     */
    public $usersGridControlFactory;

    public function renderList() {

    }

    public function createComponentUsersGrid($name)
    {
        return $this->usersGridControlFactory->create();
    }

}