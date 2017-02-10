<?php

namespace App\WebModule\Presenters;


use App\Model\ACL\Role;
use App\WebModule\Forms\AdditionalInformationForm;
use App\WebModule\Forms\AdditionalInformationFormFactory;
use App\WebModule\Forms\PersonalDetailsForm;
use App\WebModule\Forms\PersonalDetailsFormFactory;
use App\WebModule\Forms\RolesForm;
use App\WebModule\Forms\RolesFormFactory;
use Nette\Application\UI\Form;
use Skautis\Wsdl\WsdlException;

class ProfilePresenter extends WebBasePresenter
{
    /**
     * @var PersonalDetailsForm
     * @inject
     */
    public $personalDetailsFormFactory;

    /**
     * @var RolesForm
     * @inject
     */
    public $rolesFormFactory;

    /**
     * @var AdditionalInformationForm
     * @inject
     */
    public $additionalInformationFormFactory;

    private $editRegistrationAllowed;

    public function startup()
    {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            $this->flashMessage('web.common.login_required', 'danger', 'lock');
            $this->redirect(':Web:Page:default');
        }

        $nonregisteredRole = $this->roleRepository->findBySystemName(Role::NONREGISTERED);
        $this->editRegistrationAllowed = !$this->dbuser->isInRole($nonregisteredRole) && !$this->dbuser->hasPaid()
            && $this->settingsRepository->getDateValue('edit_registration_to') >= (new \DateTime())->setTime(0, 0);
    }

    public function renderDefault() {
        $this->template->pageName = $this->translator->translate('web.profile.title');
        $this->template->basicBlockDuration = $this->settingsRepository->getValue('basic_block_duration');
    }

    public function handleCancelRegistration() {
        if ($this->editRegistrationAllowed) {
            $this->userRepository->remove($this->dbuser);
            $this->presenter->redirect(':Auth:logout');
        }
    }

    public function handleExportSchedule()
    {
        //TODO export harmonogramu
    }

    protected function createComponentPersonalDetailsForm($name)
    {
        $form = $this->personalDetailsFormFactory->create($this->user->id);

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('web.profile.personal_details_update_successful', 'success');

            $this->redirect('this#collapsePersonalDetails');
        };

        $this->personalDetailsFormFactory->onSkautIsError[] = function () {
            $this->presenter->flashMessage('web.profile.personal_details_synchronization_failed', 'danger');
        };

        return $form;
    }

    protected function createComponentRolesForm($name)
    {
        $form = $this->rolesFormFactory->create($this->user->id, $this->editRegistrationAllowed);

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->redirect(':Auth:logout');
        };

        return $form;
    }

    protected function createComponentAdditionalInformationForm($name)
    {
        $form = $this->additionalInformationFormFactory->create($this->user->id);

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('web.profile.additional_information_update_successfull', 'success');

            $this->redirect('this#collapseAdditionalInformation');
        };

        return $form;
    }


}