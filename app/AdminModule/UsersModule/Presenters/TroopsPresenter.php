<?php

declare(strict_types=1);

namespace App\AdminModule\UsersModule\Presenters;

use App\AdminModule\UsersModule\Components\ITroopsGridControlFactory;
use App\AdminModule\UsersModule\Components\TroopsGridControl;
use App\Model\Acl\SrsResource;
use Nette\DI\Attributes\Inject;

/**
 * Presenter obsluhující správu skupin.
 */
class TroopsPresenter extends UsersBasePresenter
{
    protected string $resource = SrsResource::USERS;

    #[Inject]
    public ITroopsGridControlFactory $troopsGridControlFactory;

    protected function createComponentTroopsGrid(): TroopsGridControl
    {
        return $this->troopsGridControlFactory->create();
    }
}
