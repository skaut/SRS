<?php

declare(strict_types=1);

namespace App\AdminModule\UsersModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\AdminModule\UsersModule\Components\IPatrolsGridControlFactory;
use App\AdminModule\UsersModule\Components\PatrolsGridControl;
use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use Nette\Application\AbortException;
use Nette\DI\Attributes\Inject;

/**
 * Presenter obsluhující správu uživatelů.
 */
class PatrolsPresenter extends AdminBasePresenter
{
    protected string $resource = SrsResource::USERS;

    #[Inject]
    public IPatrolsGridControlFactory $patrolsGridControlFactory;

    /**
     * @throws AbortException
     */
    public function startup(): void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }

    protected function createComponentPatrolsGrid(): PatrolsGridControl
    {
        return $this->patrolsGridControlFactory->create();
    }
}
