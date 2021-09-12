<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\SeminarFormFactory;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující nastavení semináře.
 */
class SeminarPresenter extends ConfigurationBasePresenter
{
    /** @inject */
    public SeminarFormFactory $seminarFormFactory;

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    protected function createComponentSeminarForm(): Form
    {
        $form = $this->seminarFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
