<?php
declare(strict_types=1);

namespace App\WebModule\Presenters;

use App\Model\Enums\PaymentType;
use App\Model\Structure\SubeventRepository;
use App\Services\ApplicationService;
use App\Services\Authenticator;
use App\Services\ExcelExportService;
use App\Services\MailService;
use App\Services\PdfExportService;
use App\WebModule\Components\IApplicationsGridControlFactory;
use App\WebModule\Forms\IAdditionalInformationFormFactory;
use App\WebModule\Forms\PersonalDetailsForm;
use App\WebModule\Forms\RolesForm;
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
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     */
    public function startup()
    {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            $this->flashMessage('web.common.login_required', 'danger', 'lock');
            $this->redirect(':Web:Page:default');
        }
    }

    public function renderDefault()
    {
        $this->template->pageName = $this->translator->translate('web.profile.title');
        $this->template->paymentMethodBank = PaymentType::BANK;
    }

    /**
     * Vyexportuje rozvrh uživatele.
     * @throws \Nette\Application\AbortException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function actionExportSchedule()
    {
        $user = $this->userRepository->findById($this->user->id);
        $response = $this->excelExportService->exportUserSchedule($user, "harmonogram-seminare.xlsx");
        $this->sendResponse($response);
    }

    protected function createComponentPersonalDetailsForm()
    {
        $form = $this->personalDetailsFormFactory->create($this->user->id);

        $form->onSuccess[] = function (Form $form, array $values) {
            $this->flashMessage('web.profile.personal_details_update_successful', 'success');

            $this->redirect('this#collapsePersonalDetails');
        };

        $this->personalDetailsFormFactory->onSkautIsError[] = function () {
            $this->flashMessage('web.profile.personal_details_synchronization_failed', 'danger');
        };

        return $form;
    }

    protected function createComponentAdditionalInformationForm()
    {
        $control = $this->additionalInformationFormFactory->create();

        $control->onSave[] = function () {
            $this->flashMessage('web.profile.additional_information_update_successfull', 'success');
            $this->redirect('this#collapseAdditionalInformation');
        };

        return $control;
    }

    /**
     * @return Form
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     */
    protected function createComponentRolesForm()
    {
        $form = $this->rolesFormFactory->create($this->user->id);

        $form->onSuccess[] = function (Form $form, array $values) {
            if ($form['submit']->isSubmittedBy())
                $this->flashMessage('web.profile.roles_changed', 'success');
            elseif ($form['cancelRegistration']->isSubmittedBy())
                $this->flashMessage('web.profile.registration_canceled', 'success');

            $this->authenticator->updateRoles($this->user);
            $this->redirect('this#collapseSeminar');
        };
        return $form;
    }

    protected function createComponentApplicationsGrid()
    {
        return $this->applicationsGridControlFactory->create();
    }
}
