<?php

namespace BackModule;
/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends \SRS\BasePresenter
{
    public function startup() {
        parent::startup();
        if (!$this->context->user->isLoggedIn()) {
            $this->redirect(":Auth:login", array('backlink' => $this->backlink()));
        }

//        if ($this->context->user->isInRole('guest')) {
//            $this->flashMessage('Pro vstup do administrace nemáte dostatečné oprávnění');
//            $this->redirect(':Homepage:default');
//        }
    }
    

}
