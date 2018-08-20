<?php

declare(strict_types=1);

namespace App\AdminModule\CMSModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Settings\SettingsException;
use Nette\Application\AbortException;

/**
 * BasePresenter pro CMSModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class CMSBasePresenter extends AdminBasePresenter
{
    /** @var string */
    protected $resource = Resource::CMS;


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
     * @throws \Throwable
     */
    public function beforeRender() : void
    {
        parent::beforeRender();

        $this->template->sidebarVisible = true;
    }
}
