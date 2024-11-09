<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\AdminModule\Components\ApplicationsGridControl;
use App\AdminModule\Components\IApplicationsGridControlFactory;
use App\AdminModule\Components\IUsersGridControlFactory;
use App\AdminModule\Components\UsersGridControl;
use App\AdminModule\Forms\AddLectorFormFactory;
use App\AdminModule\Forms\EditUserPersonalDetailsFormFactory;
use App\AdminModule\Forms\EditUserSeminarFormFactory;
use App\AdminModule\Forms\EditUserTransferFormFactory;
use App\Model\Acl\Permission;
use App\Model\Acl\Role;
use App\Model\Acl\SrsResource;
use App\Model\CustomInput\CustomInput;
use App\Model\CustomInput\Repositories\CustomInputRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\User\Queries\UserAttendsProgramsQuery;
use App\Services\AclService;
use App\Services\ApplicationService;
use App\Services\ExcelExportService;
use JsonException;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující správu uživatelů.
 */
class UsersPresenter extends AdminBasePresenter
{
    protected string $resource = SrsResource::USERS;

    #[Inject]
    public IUsersGridControlFactory $usersGridControlFactory;

    #[Inject]
    public AddLectorFormFactory $addLectorFormFactory;

    #[Inject]
    public EditUserPersonalDetailsFormFactory $editUserPersonalDetailsFormFactory;

    #[Inject]
    public EditUserSeminarFormFactory $editUserSeminarFormFactory;

    #[Inject]
    public EditUserTransferFormFactory $editUserTransferFormFactory;

    #[Inject]
    public IApplicationsGridControlFactory $applicationsGridControlFactory;

    #[Inject]
    public ExcelExportService $excelExportService;

    #[Inject]
    public CustomInputRepository $customInputRepository;

    #[Inject]
    public AclService $aclService;

    #[Inject]
    public ApplicationService $applicationService;

    /** @throws AbortException */
    public function startup(): void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);

        $this->template->results             = [];
        $this->template->editPersonalDetails = false;
        $this->template->editSeminar         = false;
    }

    public function renderDetail(int $id): void
    {
        $user = $this->userRepository->findById($id);

        $this->template->detailUser         = $user;
        $this->template->detailUserPrograms = $this->queryBus->handle(new UserAttendsProgramsQuery($user));

        $this->template->customInputs            = $this->customInputRepository->findByRolesOrderedByPosition($user->getRoles());
        $this->template->customInputTypeCheckbox = CustomInput::CHECKBOX;
        $this->template->customInputTypeFile     = CustomInput::FILE;

        $this->template->roleAdminName     = $this->aclService->findRoleNameBySystemName(Role::ADMIN);
        $this->template->roleOrganizerName = $this->aclService->findRoleNameBySystemName(Role::ORGANIZER);

        $this->template->paymentMethodCash = PaymentType::CASH;
        $this->template->paymentMethodBank = PaymentType::BANK;

        $this->template->registered = ! $user->isInRole($this->roleRepository->findBySystemName(Role::NONREGISTERED)) && ! $user->isExternalLector();
    }

    /**
     * Zpracuje fulltext vyhledávání v displayName uživatelů.
     */
    public function handleSearch(string|null $text): void
    {
        $this->template->results = $this->userRepository->findNamesByLikeDisplayNameOrderedByDisplayName($text);
        $this->redrawControl('results');
    }

    /**
     * Zobrazí formulář pro editaci osobních údajů uživatele.
     *
     * @throws AbortException
     */
    public function handleEditPersonalDetails(): void
    {
        $this->template->editPersonalDetails = true;

        if ($this->isAjax()) {
            $this->redrawControl('userDetail');
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Zobrazí formulář pro editaci údajů o účasti uživatele na semináři.
     *
     * @throws AbortException
     */
    public function handleEditSeminar(): void
    {
        $this->template->editSeminar = true;

        if ($this->isAjax()) {
            $this->redrawControl('userDetail');
        } else {
            $this->redirect('this');
        }
    }

    /** @throws Throwable */
    public function handleCancelRegistration(): void
    {
        $user = $this->userRepository->findById((int) $this->getParameter('id'));

        $this->applicationService->cancelRegistration($user, ApplicationState::CANCELED, $this->dbUser);

        $this->flashMessage('admin.users.users_registration_canceled', 'success');
        $this->redirect('this');
    }

    /** @throws AbortException */
    public function handleRemovePhoto(): void
    {
        $user = $this->userRepository->findById((int) $this->getParameter('id'));

        $user->setPhoto(null);
        $this->userRepository->save($user);

        $this->redirect('this');
    }

    protected function createComponentUsersGrid(): UsersGridControl
    {
        return $this->usersGridControlFactory->create();
    }

    protected function createComponentAddLectorForm(): Form
    {
        $form = $this->addLectorFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            if ($form->isSubmitted() != $form['cancel']) {
                $this->flashMessage('admin.users.users_saved', 'success');
            }

            $this->redirect('Users:default');
        };

        return $form;
    }

    /** @throws JsonException */
    protected function createComponentEditUserPersonalDetailsForm(): Form
    {
        $form = $this->editUserPersonalDetailsFormFactory->create((int) $this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            if ($form->isSubmitted() != $form['cancel']) {
                $this->flashMessage('admin.users.users_saved', 'success');
            }

            $this->redirect('this');
        };

        return $form;
    }

    /** @throws JsonException */
    protected function createComponentEditUserSeminarForm(): Form
    {
        $form = $this->editUserSeminarFormFactory->create((int) $this->getParameter('id'));

        $form->onError[] = function (Form $form): void {
            foreach ($form->errors as $error) {
                $this->flashMessage($error, 'danger');
            }

            $this->redirect('this');
        };

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            if ($form->isSubmitted() != $form['cancel']) {
                $this->flashMessage('admin.users.users_saved', 'success');
            }

            $this->redirect('this');
        };

        return $form;
    }

    /** @throws JsonException */
    protected function createComponentEditUserTransferForm(): Form
    {
        $form = $this->editUserTransferFormFactory->create((int) $this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $this->flashMessage('admin.users.users_transfered', 'success');
            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentApplicationsGrid(): ApplicationsGridControl
    {
        return $this->applicationsGridControlFactory->create();
    }
}
