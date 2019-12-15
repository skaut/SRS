<?php

declare(strict_types=1);

namespace App\WebModule\Presenters;

use App\WebModule\Forms\BaseForm;
use App\Model\Enums\PaymentType;
use App\Model\Settings\SettingsException;
use App\Model\Structure\SubeventRepository;
use App\Services\ApplicationService;
use App\Services\Authenticator;
use App\Services\ExcelExportService;
use App\Services\MailService;
use App\Services\PdfExportService;
use App\WebModule\Components\ApplicationsGridControl;
use App\WebModule\Components\IApplicationsGridControlFactory;
use App\WebModule\Forms\AdditionalInformationForm;
use App\WebModule\Forms\IAdditionalInformationFormFactory;
use App\WebModule\Forms\PersonalDetailsFormFactory;
use App\WebModule\Forms\RolesFormFactory;
use Nette\Application\AbortException;
use PhpOffice\PhpSpreadsheet\Exception;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující profil uživatele.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProfilePresenter extends WebBasePresenter
{
    /**
     * @var PersonalDetailsFormFactory
     * @inject
     */
    public $personalDetailsFormFactory;

    /**
     * @var IAdditionalInformationFormFactory
     * @inject
     */
    public $additionalInformationFormFactory;

    /**
     * @var RolesFormFactory
     * @inject
     */
    public $rolesFormFactory;

    /**
     * @var IApplicationsGridControlFactory
     * @inject
     */
    public $applicationsGridControlFactory;

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
     * @var SubeventRepository
     * @inject
     */
    public $subeventRepository;

    /**
     * @var MailService
     * @inject
     */
    public $mailService;

    /**
     * @var ApplicationService
     * @inject
     */
    public $applicationService;

    /**
     * @var Authenticator
     * @inject
     */
    public $authenticator;


    /**
     * @throws AbortException
     * @throws Throwable
     */
    public function startup() : void
    {
        parent::startup();

        if ($this->user->isLoggedIn()) {
            return;
        }

        $this->flashMessage('web.common.login_required', 'danger', 'lock');
        $this->redirect(':Web:Page:default');
    }

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    public function renderDefault() : void
    {
        $this->template->pageName                  = $this->translator->translate('web.profile.title');
        $this->template->paymentMethodBank         = PaymentType::BANK;
        $this->template->isAllowedEditCustomInputs = $this->applicationService->isAllowedEditCustomInputs();
    }

    /**
     * Vyexportuje rozvrh uživatele.
     * @throws AbortException
     * @throws Exception
     */
    public function actionExportSchedule() : void
    {
        $user     = $this->userRepository->findById($this->user->id);
        $response = $this->excelExportService->exportUserSchedule($user, 'harmonogram-seminare.xlsx');
        $this->sendResponse($response);
    }

    protected function createComponentPersonalDetailsForm() : BaseForm
    {
        $form = $this->personalDetailsFormFactory->create($this->user->id);

        $form->onSuccess[] = function (BaseForm $form, stdClass $values) : void {
            $this->flashMessage('web.profile.personal_details_update_successful', 'success');

            $this->redirect('this#collapsePersonalDetails');
        };

        $this->personalDetailsFormFactory->onSkautIsError[] = function () : void {
            $this->flashMessage('web.profile.personal_details_synchronization_failed', 'danger');
        };

        return $form;
    }

    protected function createComponentAdditionalInformationForm() : AdditionalInformationForm
    {
        $control = $this->additionalInformationFormFactory->create();

        $control->onSave[] = function () : void {
            $this->flashMessage('web.profile.additional_information_update_successfull', 'success');
            $this->redirect('this#collapseAdditionalInformation');
        };

        return $control;
    }

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    protected function createComponentRolesForm() : BaseForm
    {
        $form = $this->rolesFormFactory->create($this->user->id);

        $form->onSuccess[] = function (BaseForm $form, stdClass $values) : void {
            if ($form->isSubmitted() === $form['submit']) {
                $this->flashMessage('web.profile.roles_changed', 'success');
            } elseif ($form->isSubmitted() === $form['cancelRegistration']) {
                $this->flashMessage('web.profile.registration_canceled', 'success');
            } elseif ($form->isSubmitted() === $form['downloadTicket']) {
                $this->redirect(':Export:Ticket:pdf');
            }

            $this->authenticator->updateRoles($this->user);
            $this->redirect('this#collapseSeminar');
        };
        return $form;
    }

    protected function createComponentApplicationsGrid() : ApplicationsGridControl
    {
        return $this->applicationsGridControlFactory->create();
    }
}
