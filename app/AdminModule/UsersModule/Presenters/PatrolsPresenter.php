<?php

declare(strict_types=1);

namespace App\AdminModule\UsersModule\Presenters;

use App\AdminModule\UsersModule\Components\IPatrolsGridControlFactory;
use App\AdminModule\UsersModule\Components\PatrolsGridControl;
use App\Model\Acl\SrsResource;
use Nette\DI\Attributes\Inject;

/**
 * Presenter obsluhující správu družin.
 */
class PatrolsPresenter extends UsersBasePresenter
{
    protected string $resource = SrsResource::USERS;

    #[Inject]
    public IPatrolsGridControlFactory $patrolsGridControlFactory;

    protected function createComponentPatrolsGrid(): PatrolsGridControl
    {
        return $this->patrolsGridControlFactory->create();
    }
}
