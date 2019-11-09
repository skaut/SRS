<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Settings\SettingsException;
use Nette\Application\AbortException;

/**
 * Basepresenter pro ProgramModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class ProgramBasePresenter extends AdminBasePresenter
{
    /** @var string */
    protected $resource = Resource::PROGRAM;

    /**
     * @throws AbortException
     */
    public function startup() : void
    {
        parent::startup();

        $this->checkPermission(Permission::ACCESS);
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
