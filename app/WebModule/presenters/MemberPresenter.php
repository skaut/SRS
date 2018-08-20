<?php

declare(strict_types=1);

namespace App\WebModule\Presenters;

use Nette\Application\AbortException;

/**
 * Presenter obshluhující stránku s informacemi o propojení skautIS účtu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MemberPresenter extends WebBasePresenter
{
    /**
     * @throws AbortException
     * @throws \Throwable
     */
    public function startup() : void
    {
        parent::startup();

        if ($this->user->isLoggedIn()) {
            return;
        }

        $this->flashMessage('web.common.login_required', 'danger', 'lock');
        $this->redirect(':Web:Page:default');
    }

    public function renderDefault() : void
    {
        $this->template->pageName = $this->translator->translate('web.member.title');
    }
}
