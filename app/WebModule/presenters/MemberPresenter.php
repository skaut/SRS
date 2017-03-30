<?php

namespace App\WebModule\Presenters;


/**
 * Presenter obshluhující stránku s informacemi o propojení skautIS účtu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
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
