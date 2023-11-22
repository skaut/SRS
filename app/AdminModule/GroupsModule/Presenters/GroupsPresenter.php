<?php

declare(strict_types=1);

namespace App\AdminModule\GroupsModule\Presenters;

use App\AdminModule\GroupsModule\Components\IGroupsGridControlFactory;
use App\AdminModule\GroupsModule\Components\GroupsGridControl;
use App\Model\Acl\Permission;
use App\Model\Group\Repositories\GroupRepository;
use Nette\Application\AbortException;
use Nette\DI\Attributes\Inject;

/**
 * Presenter obsluhující správu místností.
 */
class GroupsPresenter extends GroupsBasePresenter
{
    #[Inject]
    public GroupRepository $groupRepository;

    #[Inject]
    public IGroupsGridControlFactory $groupsGridControlFactory;


    /** @throws AbortException */
    public function startup(): void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }

    public function renderDetail(int $id): void
    {
        $group = $this->groupRepository->findById($id);

        $this->template->group = $group;
    }

    protected function createComponentGroupsGrid(): GroupsGridControl
    {
        return $this->groupsGridControlFactory->create();
    }
}
