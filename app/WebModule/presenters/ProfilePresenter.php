<?php

namespace App\WebModule\Presenters;


use App\WebModule\Forms\AdditionalInformationFormFactory;
use App\WebModule\Forms\PersonalDetailsFormFactory;
use App\WebModule\Forms\RolesFormFactory;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Form;

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
        //TODO
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

            // TODO

            $this->redirect($this);
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

            // TODO

            $this->redirect($this);
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

            $this->redirect('this'); // TODO zmena udaju v identite
        };

        return $form;
    }
}