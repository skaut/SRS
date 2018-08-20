<?php

declare(strict_types=1);

namespace App\WebModule\Presenters;

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
use App\WebModule\Forms\PersonalDetailsForm;
use App\WebModule\Forms\RolesForm;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use PhpOffice\PhpSpreadsheet\Exception;

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
     * @var IAdditionalInformationFormFactory
     * @inject
     */
    public $additionalInformationFormFactory;

    /**
     * @var RolesForm
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
     * @throws \Throwable
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

    public function renderDefault() : void
    {
        $this->template->pageName          = $this->translator->translate('web.profile.title');
        $this->template->paymentMethodBank = PaymentType::BANK;
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

    protected function createComponentPersonalDetailsForm() : Form
    {
        $form = $this->personalDetailsFormFactory->create($this->user->id);

        $form->onSuccess[] = function (Form $form, \stdClass $values) : void {
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
     * @throws \Throwable
     */
    protected function createComponentRolesForm() : Form
    {
        $form = $this->rolesFormFactory->create($this->user->id);

        $form->onSuccess[] = function (Form $form, \stdClass $values) : void {
            if ($form['submit']->isSubmittedBy()) {
                $this->flashMessage('web.profile.roles_changed', 'success');
            } elseif ($form['cancelRegistration']->isSubmittedBy()) {
                $this->flashMessage('web.profile.registration_canceled', 'success');
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
