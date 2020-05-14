<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use Nette\Application\AbortException;

/**
 * Basepresenter pro MailingModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class MailingBasePresenter extends AdminBasePresenter
{
    protected string $resource = SrsResource::MAILING;

    /**
     * @throws AbortException
     */
    public function startup() : void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }
}
