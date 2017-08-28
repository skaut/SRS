<?php

namespace App\AdminModule\StructureModule\Presenters;

use App\AdminModule\ProgramModule\Components\IRoomsGridControlFactory;
use App\AdminModule\StructureModule\Components\IDiscountsGridControlFactory;
use App\Model\ACL\Permission;


/**
 * Presenter obsluhující správu slev.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DiscountsPresenter extends StructureBasePresenter
{
    /**
     * @var IDiscountsGridControlFactory
     * @inject
     */
    public $discountsGridControlFactory;


    protected function createComponentDiscountsGrid()
    {
        return $this->discountsGridControlFactory->create();
    }
}
