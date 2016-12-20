<?php

namespace App\WebModule\Presenters;


class ProfilePresenter extends WebBasePresenter
{
    public function renderDefault() {
        $this->template->pageName = $this->translator->translate('web.profile.title');
    }
}