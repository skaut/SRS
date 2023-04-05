<?php

declare(strict_types=1);

namespace App\AdminModule\UsersModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use Nette\Application\AbortException;

/**
 * Basepresenter pro ProgramModule.
 */
abstract class UsersBasePresenter extends AdminBasePresenter
{
    protected string $resource = SrsResource::USERS;

    /**
     * @throws AbortException
     */
    public function startup(): void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }
}
