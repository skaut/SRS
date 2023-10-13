<?php

declare(strict_types=1);

namespace App\WebModule\Presenters;

use App\Model\Enums\PaymentType;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\Queries\UserAttendsProgramsQuery;
use App\Services\ApplicationService;
use App\Services\Authenticator;
use App\Services\ExcelExportService;
use App\WebModule\Components\ApplicationsGridControl;
use App\WebModule\Components\IApplicationsGridControlFactory;
use App\WebModule\Forms\AdditionalInformationFormFactory;
use App\WebModule\Forms\PersonalDetailsFormFactory;
use App\WebModule\Forms\RolesFormFactory;
use Exception;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující profil uživatele.
 */
class ProfilePresenter extends WebBasePresenter
{
    #[Inject]
    public PersonalDetailsFormFactory $personalDetailsFormFactory;

    #[Inject]
    public AdditionalInformationFormFactory $additionalInformationFormFactory;

    #[Inject]
    public RolesFormFactory $rolesFormFactory;

    #[Inject]
    public IApplicationsGridControlFactory $applicationsGridControlFactory;

    #[Inject]
    public ExcelExportService $excelExportService;

    #[Inject]
    public SubeventRepository $subeventRepository;

    #[Inject]
    public ApplicationService $applicationService;

    #[Inject]
    public Authenticator $authenticator;

    /**
     * @throws AbortException
     * @throws Throwable
     */
    public function startup(): void
    {
        parent::startup();

        if (! $this->user->isLoggedIn()) {
            $this->flashMessage('web.common.login_required', 'danger', 'lock');
            $this->redirect(':Web:Page:default');
        }
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function renderDefault(): void
    {
        $this->template->pageName                  = $this->translator->translate('web.profile.title');
        $this->template->paymentMethodBank         = PaymentType::BANK;
        $this->template->isAllowedEditCustomInputs = $this->applicationService->isAllowedEditCustomInputs();
        $this->template->userPrograms              = $this->queryBus->handle(new UserAttendsProgramsQuery($this->dbuser));
        $this->template->accountNumber             = $this->queryBus->handle(new SettingStringValueQuery(Settings::ACCOUNT_NUMBER));
    }

    /**
     * Vyexportuje rozvrh uživatele.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function actionExportSchedule(): void
    {
        $user     = $this->userRepository->findById($this->user->id);
        $response = $this->excelExportService->exportUserSchedule($user, 'harmonogram.xlsx');
        $this->sendResponse($response);
    }

    protected function createComponentPersonalDetailsForm(): Form
    {
        $form = $this->personalDetailsFormFactory->create($this->user->id);

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $this->flashMessage('web.profile.personal_details_update_successful', 'success');
            $this->redirect('this#personal-details');
        };

        $this->personalDetailsFormFactory->onSkautIsError[] = function (): void {
            $this->flashMessage('web.profile.personal_details_synchronization_failed', 'danger');
        };

        return $form;
    }

    /**
     * @throws Throwable
     * @throws SettingsItemNotFoundException
     */
    protected function createComponentAdditionalInformationForm(): Form
    {
        $form = $this->additionalInformationFormFactory->create($this->user->id);

        $form->onSuccess[] = function (): void {
            $this->flashMessage('web.profile.additional_information_update_successfull', 'success');
            $this->redirect('this#additional-information');
        };

        return $form;
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    protected function createComponentRolesForm(): Form
    {
        $form = $this->rolesFormFactory->create($this->user->id);

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            if ($form->isSubmitted() == $form['submit']) {
                $this->flashMessage('web.profile.roles_changed', 'success');
            } elseif ($form->isSubmitted() == $form['cancelRegistration']) {
                $this->flashMessage('web.profile.registration_canceled', 'success');
            } elseif ($form->isSubmitted() == $form['downloadTicket']) {
                $this->redirect(':Export:Ticket:pdf');
            }

            $this->authenticator->updateRoles($this->user);
            $this->redirect('this#seminar');
        };

        return $form;
    }

    protected function createComponentApplicationsGrid(): ApplicationsGridControl
    {
        return $this->applicationsGridControlFactory->create();
    }
}
