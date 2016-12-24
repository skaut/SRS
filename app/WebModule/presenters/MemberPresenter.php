<?php

namespace App\WebModule\Presenters;


class MemberPresenter extends WebBasePresenter
{
    public function startup()
    {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            $this->flashMessage('<span class="glyphicon glyphicon-lock" aria-hidden="true"></span> ' . $this->translator->translate('web.common.login_required'), 'danger');
            $this->redirect(':Web:Page:default');
        }
    }

    public function renderDefault() {
        $this->template->pageName = $this->translator->translate('web.member.title');
    }
}