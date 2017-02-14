<?php

namespace App\AdminModule\ProgramModule\Presenters;


use App\AdminModule\ProgramModule\Components\IRoomsGridControlFactory;
use App\Model\ACL\Permission;

class RoomsPresenter extends ProgramBasePresenter
{
    /**
     * @var IRoomsGridControlFactory
     * @inject
     */
    public $roomsGridControlFactory;

    public function startup()
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE_ROOMS);
    }

    protected function createComponentRoomsGrid($name)
    {
        return $this->roomsGridControlFactory->create($name);
    }
}