<?php

declare(strict_types=1);

namespace App\AdminModule\GroupsModule\Presenters;

use App\AdminModule\GroupsModule\Components\IStatusGridControlFactory;
use App\AdminModule\GroupsModule\Components\StatusGridControl;
use App\Model\Acl\Permission;
use Nette\Application\AbortException;
use Nette\DI\Attributes\Inject;

/**
 * Presenter obsluhující správu kategorií.
 */
class StatusPresenter extends GroupsBasePresenter
{
    #[Inject]
    public IStatusGridControlFactory $statusGridControlFactory;

    /** @throws AbortException */
    public function startup(): void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }

    protected function createComponentStatusGrid(): StatusGridControl
    {
        return $this->statusGridControlFactory->create();
    }
}
