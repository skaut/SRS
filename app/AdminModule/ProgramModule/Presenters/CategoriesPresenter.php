<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Presenters;

use App\AdminModule\ProgramModule\Components\IProgramCategoriesGridControlFactory;
use App\AdminModule\ProgramModule\Components\ProgramCategoriesGridControl;
use App\Model\Acl\Permission;
use Nette\Application\AbortException;

/**
 * Presenter obsluhující správu kategorií.
 */
class CategoriesPresenter extends ProgramBasePresenter
{
    /** @inject */
    public IProgramCategoriesGridControlFactory $programCategoriesGridControlFactory;

    /**
     * @throws AbortException
     */
    public function startup(): void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE_CATEGORIES);
    }

    protected function createComponentProgramCategoriesGrid(): ProgramCategoriesGridControl
    {
        return $this->programCategoriesGridControlFactory->create();
    }
}
