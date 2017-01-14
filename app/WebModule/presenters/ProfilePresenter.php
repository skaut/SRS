<?php

namespace App\WebModule\Presenters;


use App\WebModule\Forms\AdditionalInformationFormFactory;
use App\WebModule\Forms\PersonalDetailsFormFactory;
use App\WebModule\Forms\RolesFormFactory;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Form;
use Skautis\Skautis;

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

    public function startup()
    {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            $this->flashMessage('<span class="glyphicon glyphicon-lock" aria-hidden="true"></span> ' . $this->translator->translate('web.common.login_required'), 'danger');
            $this->redirect(':Web:Page:default');
        }
    }

    public function renderDefault() {
        $this->template->pageName = $this->translator->translate('web.profile.title');
        $this->template->cancelRegistrationAllowed = $this->settingsRepository->getDateValue('cancel_registration_to') >= new \DateTime();
    }

    public function handleExportSchedule()
    {
        // TODO export harmonogramu
    }

    protected function createComponentPersonalDetailsForm()
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
                $skautISPerson = $this->skautIS->org->PersonDetail([
                    'ID_Login' => $this->skautIS->getUser()->getLoginId(),
                    'ID' => $editedUser->getSkautISPersonId(),
                ], 'personDetailInput');

                $this->skautIS->org->PersonUpdateBasic([
                    'ID_Login' => $this->skautIS->getUser()->getLoginId(),
                    'ID' => $editedUser->getSkautISPersonId(),
                    'ID_Sex' => $editedUser->getSex(),
                    'Birthday' => $editedUser->getBirthdate()->format('Y-m-d\TH:i:s'),
                    'FirstName' => $editedUser->getFirstName(),
                    'LastName' => $editedUser->getLastName(),
                    'NickName' => $editedUser->getNickName()
                ], 'personUpdateBasicInput');

                $this->skautIS->org->PersonUpdateAddress([
                    'ID_Login' => $this->skautIS->getUser()->getLoginId(),
                    'ID' => $editedUser->getSkautISPersonId(),
                    'Street' => $editedUser->getStreet(),
                    'City' => $editedUser->getCity(),
                    'Postcode' => $editedUser->getPostcode(),
                    'State' => $editedUser->getState(),
                    'PostalFirstLine' => $skautISPerson->PostalFirstLine,
                    'PostalStreet' => $skautISPerson->PostalStreet,
                    'PostalCity' => $skautISPerson->PostalCity,
                    'PostalPostcode' => $skautISPerson->PostalPostcode,
                    'PostalState' => $skautISPerson->PostalState
                ], 'personUpdateAddressInput');
            } catch (\Skautis\Wsdl\WsdlException $ex) {
                $this->presenter->flashMessage('Synchronizace se skautIS se nepodařila. Zkuste se znovu přihlásit.', 'danger');
            }

            $this->flashMessage('Osobní údaje aktualizovány.', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentRolesForm()
    {
        $form = $this->rolesFormFactory->create($this->dbuser);

        $usersRolesIds = array_map(function($o) { return $o->getId(); }, $this->dbuser->getRoles()->toArray());
        $form->setDefaults([
            'id' => $this->dbuser->getId(),
            'roles' => $usersRolesIds
        ]);

        $form->onSuccess[] = function (Form $form) {
            $values = $form->getValues();

            if ($form['submit']->isSubmittedBy())
                $this->changeRoles($values);
            elseif ($form['cancelRegistration']->isSubmittedBy())
                $this->cancelRegistration($values);
        };

        return $form;
    }

    protected function createComponentAdditionalInformationForm()
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

            $this->flashMessage('Doplňující informace upraveny.', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    private function changeRoles($values) {
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
    }

    private function cancelRegistration($values) {
        $this->redirect('this');
    }
}