<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use Nette\Application\AbortException;

/**
 * BasePresenter pro CmsModule.
 */
abstract class CmsBasePresenter extends AdminBasePresenter
{
    protected string $resource = SrsResource::CMS;

    /** @throws AbortException */
    public function startup(): void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }
}
