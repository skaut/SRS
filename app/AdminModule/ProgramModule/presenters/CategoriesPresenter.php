<?php

namespace App\AdminModule\ProgramModule\Presenters;

use App\AdminModule\ProgramModule\Components\IProgramCategoriesGridControlFactory;
use App\Model\ACL\Permission;


class CategoriesPresenter extends ProgramBasePresenter
{
    /**
     * @var IProgramCategoriesGridControlFactory
     * @inject
     */
    public $programCategoriesGridControlFactory;


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