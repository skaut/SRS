<?php

namespace App\AdminModule\ProgramModule\Presenters;

use App\AdminModule\ProgramModule\Components\IProgramCategoriesGridControlFactory;
use App\Model\ACL\Permission;


/**
 * Presenter obsluhující správu kategorií.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CategoriesPresenter extends ProgramBasePresenter
{
    /**
     * @var IProgramCategoriesGridControlFactory
     * @inject
     */
    public $programCategoriesGridControlFactory;


    /**
     * @throws \Nette\Application\AbortException
     */
    public function startup()
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE_CATEGORIES);
    }

    protected function createComponentProgramCategoriesGrid()
    {
        return $this->programCategoriesGridControlFactory->create();
    }
}
