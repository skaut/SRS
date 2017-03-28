<?php

namespace App\AdminModule\ProgramModule\Presenters;

use App\AdminModule\ProgramModule\Components\IRoomsGridControlFactory;
use App\Model\ACL\Permission;


/**
 * Presenter obsluhující správu místností.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
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

    protected function createComponentRoomsGrid()
    {
        return $this->roomsGridControlFactory->create();
    }
}