<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\GroupFormFactory;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující nastavení skupin.
 */
class GroupPresenter extends ConfigurationBasePresenter
{
    #[Inject]
    public GroupFormFactory $groupFormFactory;

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    protected function createComponentGroupForm(): Form
    {
        $form = $this->groupFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $form;
    }
}
