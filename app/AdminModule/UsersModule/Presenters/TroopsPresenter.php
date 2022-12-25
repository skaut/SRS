<?php

declare(strict_types=1);

namespace App\AdminModule\UsersModule\Presenters;

use App\AdminModule\UsersModule\Components\ITroopsGridControlFactory;
use App\AdminModule\UsersModule\Components\TroopsGridControl;
use App\Model\Acl\SrsResource;
use App\Model\User\Repositories\TroopRepository;
use Nette\DI\Attributes\Inject;

/**
 * Presenter obsluhující správu skupin.
 */
class TroopsPresenter extends UsersBasePresenter
{
    protected string $resource = SrsResource::USERS;

    #[Inject]
    public ITroopsGridControlFactory $troopsGridControlFactory;

    #[Inject]
    public TroopRepository $troopRepository;

    protected function createComponentTroopsGrid(): TroopsGridControl
    {
        return $this->troopsGridControlFactory->create();
    }

    public function renderDetail(int $id): void
    {
        $troop = $this->troopRepository->findById($id);
        $this->template->troop = $troop;
    }
}
