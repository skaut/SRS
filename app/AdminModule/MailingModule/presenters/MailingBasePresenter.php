<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\ACL\Permission;
use App\Model\ACL\SrsResource;
use App\Model\Settings\SettingsException;
use Nette\Application\AbortException;
use Throwable;

/**
 * Basepresenter pro MailingModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class MailingBasePresenter extends AdminBasePresenter
{
    /** @var string */
    protected $resource = SrsResource::MAILING;


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

        $this->template->sidebarVisible = true;
    }
}
