<?php

namespace App\WebModule\Presenters;


class MemberPresenter extends WebBasePresenter
{
    public function startup()
    {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            $this->flashMessage('web.common.login_required', 'danger', 'lock');
            $this->redirect(':Web:Page:default');
        }
    }

    public function renderDefault()
    {
        $this->template->pageName = $this->translator->translate('web.member.title');
    }
}