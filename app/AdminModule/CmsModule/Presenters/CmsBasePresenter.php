<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use Nette\Application\AbortException;

/**
 * BasePresenter pro CmsModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class CmsBasePresenter extends AdminBasePresenter
{
    /** @var string */
    protected $resource = SrsResource::CMS;

    /**
     * @throws AbortException
     */
    public function startup() : void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }
}
