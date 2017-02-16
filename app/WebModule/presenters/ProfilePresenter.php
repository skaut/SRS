<?php

namespace App\WebModule\Presenters;


use App\Model\ACL\Role;
use App\Model\Enums\PaymentType;
use App\Services\Authenticator;
use App\Services\ExcelExportService;
use App\Services\PdfExportService;
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
        $this->template->paymentMethodBank = PaymentType::BANK;
    }

    public function actionGeneratePaymentProofBank() {
        $user = $this->userRepository->findById($this->user->id);
        $user->setIncomeProofPrintedDate(new \DateTime());
        $this->userRepository->save($user);
        $this->pdfExportService->generatePaymentProof($user, "potvrzeni-o-prijeti-platby.pdf");
    }

    public function actionExportSchedule()
    {
        $user = $this->userRepository->findById($this->user->id);
        $response = $this->excelExportService->exportUsersSchedules($user, "harmonogram-seminare.xlsx");
        $this->sendResponse($response);
    }

    protected function createComponentPersonalDetailsForm($name)
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

    protected function createComponentRolesForm($name)
    {
        $form = $this->rolesFormFactory->create($this->user->id, $this->editRegistrationAllowed);

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($form['submit']->isSubmittedBy()) {
                $this->flashMessage('web.profile.roles_update_successful', 'success');

                $this->authenticator->updateRoles($this->user);

                $this->redirect('this#collapseSeminar');
            }
            elseif ($form['cancelRegistration']->isSubmittedBy()) {
                $this->redirect(':Auth:logout');
            }
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