<?php

namespace App\WebModule\Presenters;


use Nette\Application\UI\Form;

class ProfilePresenter extends WebBasePresenter
{
    /**
     * @var \App\WebModule\Forms\PersonalDetailsFormFactory
     * @inject
     */
    public $personalDetailsFormFactory;

    public function renderDefault() {
        $this->template->pageName = $this->translator->translate('web.profile.title');
        $this->template->usersPayingRoles = $this->user->identity->dbuser->getPayingRoles();
        $this->template->cancelRegistrationAllowed = $this->settingsRepository->getDateValue('cancel_registration_to') >= new \DateTime();
    }

    public function handleExportSchedule()
    {

    }

    protected function createComponentPersonalDetailsForm()
    {
        $form = $this->personalDetailsFormFactory->create($this->user->identity->dbuser->isMember());

        $form->onSuccess[] = function (Form $form) {
            $values = $form->getValues();

            $this->redirect($this);
        };

        return $form;
    }

    protected function createComponentRolesForm()
    {
        return new Form();
    }

    protected function createComponentAdditionalInformationForm()
    {
        return new Form();
    }
}