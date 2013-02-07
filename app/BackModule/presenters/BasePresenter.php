<?php

namespace BackModule;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends \SRS\BasePresenter
{
    /**
     * @var \SRS\Model\SettingsRepository
     */
    protected $dbsettings;

    public function startup() {
        parent::startup();
        if (!$this->context->user->isLoggedIn()) {
            $this->redirect(":Auth:login", array('backlink' => $this->backlink()));
        }

        $this->dbsettings = $this->presenter->context->database->getRepository('\SRS\Model\Settings');


        if (!$this->context->user->isAllowed('Administrace', 'Přístup' )) {
            $this->flashMessage('Pro vstup do administrace nemáte dostatečné oprávnění');
            $this->redirect(':Front:Page:Default');
        }
    }

    protected function checkPermissions($permission) {
        if (!$this->context->user->isAllowed($this->resource, $permission )) {
            $this->flashMessage('Nemáte dostatečné oprávnění');
            $this->redirect(':Back:Dashboard:Default');
        }

    }
    

}
