<?php

declare(strict_types=1);

namespace App\AdminModule\UsersModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\AdminModule\UsersModule\Components\ITroopsGridControlFactory;
use App\AdminModule\UsersModule\Components\TroopsGridControl;
use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use Nette\Application\AbortException;
use Nette\DI\Attributes\Inject;

/**
 * Presenter obsluhující správu uživatelů.
 */
class TroopsPresenter extends AdminBasePresenter
{
    protected string $resource = SrsResource::USERS;

    #[Inject]
    public ITroopsGridControlFactory $troopsGridControlFactory;

    /**
     * @throws AbortException
     */
    public function startup(): void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }

    protected function createComponentTroopsGrid(): TroopsGridControl
    {
        return $this->troopsGridControlFactory->create();
    }
}
