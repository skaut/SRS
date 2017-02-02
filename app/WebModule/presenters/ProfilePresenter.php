<?php

namespace App\WebModule\Presenters;


use App\Model\ACL\Role;
use App\WebModule\Forms\AdditionalInformationFormFactory;
use App\WebModule\Forms\PersonalDetailsFormFactory;
use App\WebModule\Forms\RolesFormFactory;
use Nette\Application\UI\Form;
use Skautis\Wsdl\WsdlException;

class ProfilePresenter extends WebBasePresenter
{
    /**
     * @var PersonalDetailsFormFactory
     * @inject
     */
    public $personalDetailsFormFactory;

    /**
     * @var RolesFormFactory
     * @inject
     */
    public $rolesFormFactory;

    /**
     * @var AdditionalInformationFormFactory
     * @inject
     */
    public $additionalInformationForm;

    private $editRegistrationAllowed;

    public function startup()
    {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            $this->flashMessage('web.common.login_required', 'danger', 'lock');
            $this->redirect(':Web:Page:default');
        }

        $unregisteredRole = $this->roleRepository->findRoleByUntranslatedName(Role::UNREGISTERED);
        $this->editRegistrationAllowed = !$this->dbuser->isInRole($unregisteredRole) && !$this->dbuser->hasPaid()
            && $this->settingsRepository->getDateValue('edit_registration_to') >= (new \DateTime())->setTime(0, 0);
    }

    public function renderDefault() {
        $this->template->pageName = $this->translator->translate('web.profile.title');
        $this->template->basicBlockDuration = $this->settingsRepository->getValue('basic_block_duration');
    }

    public function handleCancelRegistration() {
        if ($this->editRegistrationAllowed) {
            $this->userRepository->removeUser($this->dbuser);
            $this->presenter->redirect(':Auth:logout');
        }
    }

    public function handleExportSchedule()
    {
        //TODO export harmonogramu
    }

    protected function createComponentPersonalDetailsForm($name)
    {
        $form = $this->personalDetailsFormFactory->create($this->dbuser);

        $form->setDefaults([
            'id' => $this->dbuser->getId(),
            'sex' => $this->dbuser->getSex(),
            'firstName' => $this->dbuser->getFirstName(),
            'lastName' => $this->dbuser->getLastName(),
            'nickName' => $this->dbuser->getNickName(),
            'birthdate' => $this->dbuser->getBirthdate(),
            'street' => $this->dbuser->getStreet(),
            'city' => $this->dbuser->getCity(),
            'postcode' => $this->dbuser->getPostcode(),
            'state' => $this->dbuser->getState()
        ]);

        $form->onSuccess[] = function (Form $form) {
            $values = $form->getValues();

            $editedUser = $this->userRepository->find($values['id']);

            if (array_key_exists('sex', $values))
                $editedUser->setSex($values['sex']);
            if (array_key_exists('firstName', $values))
                $editedUser->setFirstName($values['firstName']);
            if (array_key_exists('lastName', $values))
                $editedUser->setLastName($values['lastName']);
            if (array_key_exists('nickName', $values))
                $editedUser->setNickName($values['nickName']);
            if (array_key_exists('birthdate', $values))
                $editedUser->setBirthdate($values['birthdate']);
            $editedUser->setStreet($values['street']);
            $editedUser->setCity($values['city']);
            $editedUser->setPostcode($values['postcode']);
            $editedUser->setState($values['state']);

            $this->userRepository->getEntityManager()->flush();

            try {
                $this->skautIsService->updatePersonBasic($editedUser->getSkautISPersonId(), $editedUser->getSex(),
                    $editedUser->getBirthdate(), $editedUser->getFirstName(), $editedUser->getLastName(),
                    $editedUser->getNickName());

                $this->skautIsService->updatePersonAddress($editedUser->getSkautISPersonId(), $editedUser->getStreet(),
                    $editedUser->getCity(), $editedUser->getPostcode(), $editedUser->getState());
            } catch (WsdlException $ex) {
                $this->presenter->flashMessage('web.profile.personal_details_synchronization_failed', 'danger');
            }

            $this->flashMessage('web.profile.personal_details_update_successful', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentRolesForm($name)
    {
        $form = $this->rolesFormFactory->create($this->dbuser, $this->editRegistrationAllowed);

        $usersRolesIds = array_map(function($o) { return $o->getId(); }, $this->dbuser->getRoles()->toArray());
        $form->setDefaults([
            'id' => $this->dbuser->getId(),
            'roles' => $usersRolesIds
        ]);

        $form->onSuccess[] = function (Form $form) {
            $values = $form->getValues();

            $editedUser = $this->userRepository->find($values['id']);

            $selectedRoles = array();
            foreach ($values['roles'] as $roleId) {
                $selectedRoles[] = $this->roleRepository->find($roleId);
            }

            //pokud si uživatel přidá roli, která vyžaduje schválení, stane se neschválený
            $approved = $editedUser->isApproved();
            if ($approved) {
                foreach ($selectedRoles as $role) {
                    if (!$role->isApprovedAfterRegistration() && !$editedUser->getRoles()->contains($role)) {
                        $approved = false;
                        break;
                    }
                }
            }

            $editedUser->updateRoles($selectedRoles);
            $editedUser->setApproved($approved);

            $this->userRepository->getEntityManager()->flush();

            $this->redirect(':Auth:logout');
        };

        return $form;
    }

    protected function createComponentAdditionalInformationForm($name)
    {
        $form = $this->additionalInformationForm->create($this->dbuser);

        $form->setDefaults([
            'id' => $this->dbuser->getId(),
            'about' => $this->dbuser->getAbout(),
            'arrival' => $this->dbuser->getArrival(),
            'departure' => $this->dbuser->getDeparture()
        ]);

        $form->onSuccess[] = function (Form $form) {
            $values = $form->getValues();

            $editedUser = $this->userRepository->find($values['id']);
            $editedUser->setAbout($values['about']);

            if (array_key_exists('arrival', $values))
                $editedUser->setArrival($values['arrival']);
            if (array_key_exists('departure', $values))
                $editedUser->setDeparture($values['departure']);

            $this->userRepository->getEntityManager()->flush();

            $this->flashMessage('web.profile.additional_information_update_successfull', 'success');

            $this->redirect('this');
        };

        return $form;
    }


}