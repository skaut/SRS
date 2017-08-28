<?php

namespace App\AdminModule\StructureModule\Presenters;

use App\AdminModule\ProgramModule\Components\IRoomsGridControlFactory;
use App\AdminModule\StructureModule\Components\ISubeventsGridControlFactory;
use App\Model\ACL\Permission;


/**
 * Presenter obsluhující správu podakcí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SubeventsPresenter extends StructureBasePresenter
{
    /**
     * @var ISubeventsGridControlFactory
     * @inject
     */
    public $subeventsGridControlFactory;


    protected function createComponentSubeventsGrid()
    {
        return $this->subeventsGridControlFactory->create();
    }
}
