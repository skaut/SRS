<?php
/**
 * Date: 17.2.13
 * Time: 11:30
 * Author: Michal Májský
 */
namespace FrontModule;

/**
 * Obsluhuje nastaveni profilu
 */
class ProfilePresenter extends BasePresenter
{
    protected $userRepo;

    protected $skautIS;

    public function startup()
    {
        parent::startup();

        $this->userRepo = $this->context->database->getRepository('\SRS\Model\User');
        $this->skautIS = $this->context->skautIS;

        if (!$this->context->user->isLoggedIn()) {
            $this->flashMessage('Pro přístup do profilu musíte být přihlášeni', 'error');
            $this->redirect(':Front:Page:default');
        }
    }

    public function renderDefault()
    {
        /**
         * @var \SRS\Model\User
         */
        $user = $this->userRepo->find($this->context->user->id);
        $skautISPerson = $this->skautIS->getPerson($this->user->identity->token, $user->skautISPersonId);
        $user->generateVariableSymbol($this->context->database);

        $form = $this['profileForm'];
        $form->bindEntity($user);

        $form = $this['aboutForm'];
        $form->bindEntity($user);

        $form = $this['rolesForm'];
        $form->bindEntity($user);

        $birthday = \explode("T", $skautISPerson->Birthday);
        $skautISPerson->birthdate = $birthday[0];

        $this->template->skautISPerson = $skautISPerson;
        $this->template->dbuser = $user;
        $this->template->basicBlockDuration = $this->dbsettings->get('basic_block_duration');
        $this->template->displayCancelRegistration = \DateTime::createFromFormat("d.m.Y", $this->dbsettings->get('cancel_registration_to_date')) >= new \DateTime() ? true : false;

        $usersPayingRoles = $this->userRepo->findUsersPayingRoles($user->id);
        $this->template->usersPayingRoles = $usersPayingRoles;
        $this->template->usersPayingRolesCount = count($usersPayingRoles);
    }

    public function handlePrintProof()
    {
        $user = $this->userRepo->find($this->context->user->id);
        $user->incomeProofPrintedDate = new \DateTime();
        $this->context->database->flush();

        $printer = $this->context->printer;
        $printer->printPaymentProofs(array($user));
    }

    public function handleCancelRegistration()
    {
        $user = $this->userRepo->find($this->context->user->id);
        $this->context->database->remove($user);
        $this->context->database->flush();
        $this->presenter->user->logout(true);
        $this->presenter->redirect(':Auth:logout');
    }

    protected function createComponentProfileForm()
    {
        $form = new \SRS\Form\ProfileForm();
        $form->inject($this->context->database, $this->skautIS);
        return $form;
    }

    protected function createComponentAboutForm()
    {
        $form = new \SRS\Form\Evidence\AboutForm(null, null);
        return $form;
    }

    protected function createComponentRolesForm()
    {
        $form = new \SRS\Form\RolesForm(null, null, $this->context->database, $this->userRepo->find($this->context->user->id));
        return $form;
    }
}
