<?php

declare(strict_types=1);

namespace App\AdminModule\PaymentsModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use App\Model\Settings\SettingsException;
use Nette\Application\AbortException;
use Throwable;

/**
 * Basepresenter pro PaymentsModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class PaymentsBasePresenter extends AdminBasePresenter
{
    /** @var string */
    protected $resource = SrsResource::PAYMENTS;

    /**
     * @throws AbortException
     */
    public function startup() : void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    public function beforeRender() : void
    {
        parent::beforeRender();

        $this->template->sidebarVisible = false;
    }
}
