<?php

namespace App\WebModule\Presenters;

use App\Model\ACL\Role;
use App\Model\Enums\PaymentType;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Settings;
use App\Model\Structure\SubeventRepository;
use App\Services\Authenticator;
use App\Services\ExcelExportService;
use App\Services\MailService;
use App\Services\PdfExportService;
use App\WebModule\Forms\AdditionalInformationForm;
use App\WebModule\Forms\PersonalDetailsForm;
use App\WebModule\Forms\RolesForm;
use App\WebModule\Forms\SubeventsForm;
use Doctrine\Common\Collections\ArrayCollection;
use Nette\Application\UI\Form;


/**
 * Presenter obsluhující profil uživatele.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProfilePresenter extends WebBasePresenter
{
    /**
     * @var PersonalDetailsForm
     * @inject
     */
    public $personalDetailsFormFactory;

    /**
     * @var SubeventsForm
     * @inject
     */
    public $subeventsFormFactory;

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

    /**
     * @var PdfExportService
     * @inject
     */
    public $pdfExportService;

    /**
     * @var ExcelExportService
     * @inject
     */
    public $excelExportService;

    /**
     * @var Authenticator
     * @inject
     */
    public $authenticator;

    /**
     * @var SubeventRepository
     * @inject
     */
    public $subeventRepository;

    /**
     * @var MailService
     * @inject
     */
    public $mailService;

    private $editRegistrationAllowed;


    public function startup()
    {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            $this->flashMessage('web.common.login_required', 'danger', 'lock');
            $this->redirect(':Web:Page:default');
        }

        $nonregisteredRole = $this->roleRepository->findBySystemName(Role::NONREGISTERED);
        $this->editRegistrationAllowed = !$this->dbuser->isInRole($nonregisteredRole) && !$this->dbuser->hasPaidAnyApplication()
            && $this->settingsRepository->getDateValue('edit_registration_to') >= (new \DateTime())->setTime(0, 0);
    }

    public function renderDefault()
    {
        $this->template->pageName = $this->translator->translate('web.profile.title');
        $this->template->paymentMethodBank = PaymentType::BANK;
        $this->template->editRegistrationAllowed = $this->editRegistrationAllowed;
        $this->template->subeventsCount = $this->subeventRepository->countExplicitSubevents();
    }

    /**
     * Odhlásí uživatele ze semináře.
     */
    public function actionCancelRegistration()
    {
        $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$this->dbuser]), '', Template::REGISTRATION_CANCELED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME)
        ]);

        $this->userRepository->remove($this->dbuser);

        $this->redirect(':Auth:logout');
    }

    /**
     * Vygeneruje potvrzení o přijetí platby.
     */
    public function actionGeneratePaymentProofBank()
    {
        $user = $this->userRepository->findById($this->user->id);
        if (!$user->getIncomeProofPrintedDate()) { //TODO
            $user->setIncomeProofPrintedDate(new \DateTime());
            $this->userRepository->save($user);
        }
        $this->pdfExportService->generatePaymentProof($user, "potvrzeni-o-prijeti-platby.pdf");
    }

    /**
     * Vyexportuje rozvrh uživatele.
     */
    public function actionExportSchedule()
    {
        $user = $this->userRepository->findById($this->user->id);
        $response = $this->excelExportService->exportUsersSchedule($user, "harmonogram-seminare.xlsx");
        $this->sendResponse($response);
    }

    protected function createComponentPersonalDetailsForm()
    {
        $form = $this->personalDetailsFormFactory->create($this->user->id);

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('web.profile.personal_details_update_successful', 'success');

            $this->redirect('this#collapsePersonalDetails');
        };

        $this->personalDetailsFormFactory->onSkautIsError[] = function () {
            $this->flashMessage('web.profile.personal_details_synchronization_failed', 'danger');
        };

        return $form;
    }

    protected function createComponentSubeventsForm()
    {
        $editSubeventsAllowed = FALSE; //TODO

        $form = $this->subeventsFormFactory->create($this->user->id, $editSubeventsAllowed);

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('web.profile.subevents_update_successful', 'success');

            $this->authenticator->updateRoles($this->user);

            $this->redirect('this#collapseSeminar');
        };

        return $form;
    }

    protected function createComponentRolesForm()
    {
        $form = $this->rolesFormFactory->create($this->user->id, $this->editRegistrationAllowed);

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('web.profile.roles_update_successful', 'success');

            $this->authenticator->updateRoles($this->user);

            $this->redirect('this#collapseSeminar');
        };

        return $form;
    }

    protected function createComponentAdditionalInformationForm()
    {
        $form = $this->additionalInformationFormFactory->create($this->user->id);

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('web.profile.additional_information_update_successfull', 'success');

            $this->redirect('this#collapseAdditionalInformation');
        };

        return $form;
    }
}
