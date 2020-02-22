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
use App\Model\Acl\Permission;
use App\Model\Acl\Role;
use App\Model\Acl\SrsResource;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Services\ApplicationService;
use App\Services\ExcelExportService;
use App\Services\PdfExportService;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující správu uživatelů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class UsersPresenter extends AdminBasePresenter
{
    /** @var string */
    protected $resource = SrsResource::USERS;

    /**
     * @var IUsersGridControlFactory
     * @inject
     */
    public $usersGridControlFactory;

    /**
     * @var AddLectorFormFactory
     * @inject
     */
    public $addLectorFormFactory;

    /**
     * @var EditUserPersonalDetailsFormFactory
     * @inject
     */
    public $editUserPersonalDetailsFormFactory;

    /**
     * @var EditUserSeminarFormFactory
     * @inject
     */
    public $editUserSeminarFormFactory;

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
     * @var CustomInputRepository
     * @inject
     */
    public $customInputRepository;

    /**
     * @var ApplicationService
     * @inject
     */
    public $applicationService;

    /**
     * @throws AbortException
     */
    public function startup() : void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);

        $this->template->results             = [];
        $this->template->editPersonalDetails = false;
        $this->template->editSeminar         = false;
        $this->template->editPayment         = false;
    }

    public function renderDetail(int $id) : void
    {
        $user = $this->userRepository->findById($id);

        $this->template->detailUser = $user;

        $this->template->customInputs            = $this->customInputRepository->findAllOrderedByPosition();
        $this->template->customInputTypeText     = CustomInput::TEXT;
        $this->template->customInputTypeCheckbox = CustomInput::CHECKBOX;
        $this->template->customInputTypeSelect   = CustomInput::SELECT;
        $this->template->customInputTypeFile     = CustomInput::FILE;

        $this->template->roleAdminName     = $this->roleRepository->findBySystemName(Role::ADMIN)->getName();
        $this->template->roleOrganizerName = $this->roleRepository->findBySystemName(Role::ORGANIZER)->getName();

        $this->template->paymentMethodCash = PaymentType::CASH;
        $this->template->paymentMethodBank = PaymentType::BANK;

        $this->template->registered = ! $user->isInRole($this->roleRepository->findBySystemName(Role::NONREGISTERED)) && ! $user->isExternalLector();
    }

    /**
     * Zpracuje fulltext vyhledávání v displayName uživatelů.
     */
    public function handleSearch(?string $text) : void
    {
        $this->template->results = $this->userRepository->findNamesByLikeDisplayNameOrderedByDisplayName($text);
        $this->redrawControl('results');
    }

    /**
     * Zobrazí formulář pro editaci osobních údajů uživatele.
     *
     * @throws AbortException
     */
    public function handleEditPersonalDetails() : void
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
    public function handleEditSeminar() : void
    {
        $this->template->editSeminar = true;

        if ($this->isAjax()) {
            $this->redrawControl('userDetail');
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Zobrazí formulář pro editaci údajů o platbě uživatele.
     *
     * @throws AbortException
     */
    public function handleEditPayment() : void
    {
        $this->template->editPayment = true;

        if ($this->isAjax()) {
            $this->redrawControl('userDetail');
        } else {
            $this->redirect('this');
        }
    }

    /**
     * @throws Throwable
     */
    public function handleCancelRegistration() : void
    {
        $user       = $this->userRepository->findById((int) $this->getParameter('id'));
        $loggedUser = $this->userRepository->findById($this->user->id);

        $this->applicationService->cancelRegistration($user, ApplicationState::CANCELED, $loggedUser);

        $this->flashMessage('admin.users.users_registration_canceled', 'success');
        $this->redirect('this');
    }

    protected function createComponentUsersGrid() : UsersGridControl
    {
        return $this->usersGridControlFactory->create();
    }

    protected function createComponentAddLectorForm() : Form
    {
        $form = $this->addLectorFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values) : void {
            if ($form->isSubmitted() === $form['cancel']) {
                $this->redirect('Users:default');
            } else {
                $this->flashMessage('admin.users.users_saved', 'success');
                $this->redirect('Users:default');
            }
        };

        return $form;
    }

    protected function createComponentEditUserPersonalDetailsForm() : Form
    {
        $form = $this->editUserPersonalDetailsFormFactory->create((int) $this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, stdClass $values) : void {
            if ($form->isSubmitted() === $form['cancel']) {
                $this->redirect('this');
            } else {
                $this->flashMessage('admin.users.users_saved', 'success');
                $this->redirect('this');
            }
        };

        return $form;
    }

    protected function createComponentEditUserSeminarForm() : Form
    {
        $form = $this->editUserSeminarFormFactory->create((int) $this->getParameter('id'));

        $form->onError[] = function (Form $form) : void {
            foreach ($form->errors as $error) {
                $this->flashMessage($error, 'danger');
            }

            $this->redirect('this');
        };

        $form->onSuccess[] = function (Form $form, stdClass $values) : void {
            if ($form->isSubmitted() !== $form['cancel']) {
                $this->flashMessage('admin.users.users_saved', 'success');
            }

            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentApplicationsGrid() : ApplicationsGridControl
    {
        return $this->applicationsGridControlFactory->create();
    }
}
