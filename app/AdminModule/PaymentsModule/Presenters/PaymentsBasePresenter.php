<?php

declare(strict_types=1);

namespace App\AdminModule\PaymentsModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use Nette\Application\AbortException;

/**
 * Basepresenter pro PaymentsModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class PaymentsBasePresenter extends AdminBasePresenter
{
    protected string $resource = SrsResource::PAYMENTS;

    /**
     * @throws AbortException
     */
    public function startup(): void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }
}
