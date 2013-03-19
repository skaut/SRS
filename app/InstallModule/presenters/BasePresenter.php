<?php

namespace InstallModule;

abstract class BasePresenter extends \SRS\BasePresenter
{
    public function startup()
    {
        parent::startup();
        if (!$this->context->user->isLoggedIn()) {
            $this->redirect(":Auth:login", array('backlink' => $this->backlink()));
        }

    }


}
