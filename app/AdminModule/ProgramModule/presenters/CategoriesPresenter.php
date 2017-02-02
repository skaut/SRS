<?php

namespace App\AdminModule\ProgramModule\Presenters;


use App\AdminModule\ProgramModule\Components\IProgramCategoriesGridControlFactory;

class CategoriesPresenter extends ProgramBasePresenter
{
    /**
     * @var IProgramCategoriesGridControlFactory
     * @inject
     */
    public $programCategoriesGridControlFactory;

    protected function createComponentProgramCategoriesGrid($name)
    {
        return $this->programCategoriesGridControlFactory->create($name);
    }
}