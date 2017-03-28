<?php

namespace App\AdminModule\MailingModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;


/**
 * Basepresenter pro MailingModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class MailingBasePresenter extends AdminBasePresenter
{
    protected $resource = Resource::MAILING;


    public function startup()
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }

    public function beforeRender()
    {
        parent::beforeRender();

        $this->template->sidebarVisible = true;
    }
}