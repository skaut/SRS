<?php

namespace BackModule;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base presenter pro administraci
 */
abstract class BasePresenter extends \SRS\BasePresenter
{
    protected $dbuser;


    public function startup()
    {
        parent::startup();
        if (!$this->context->user->isLoggedIn()) {
            $this->redirect(":Auth:login", array('backlink' => $this->backlink()));
        }

        if (!$this->isAjax()) { //kvuli ajaxovym pozadavkym na program
            if (!$this->context->user->isAllowed('Administrace', 'Přístup')) {
                $this->flashMessage('Pro vstup do administrace nemáte dostatečné oprávnění');
                $this->redirect(':Front:Page:Default');
            }
        }

        $this->dbuser = $this->context->database->getRepository('\SRS\Model\User')->find($this->context->user->id);

    }

    protected function checkPermissions($permission)
    {
        if (!$this->context->user->isAllowed($this->resource, $permission)) {
            $this->flashMessage('Nemáte dostatečné oprávnění');
            $this->redirect(':Back:Dashboard:Default');
        }
    }


}
