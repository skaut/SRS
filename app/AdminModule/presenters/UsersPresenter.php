<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Components\IApplicationsGridControlFactory;
use App\AdminModule\Components\IUsersGridControlFactory;
use App\AdminModule\Forms\AddLectorForm;
use App\AdminModule\Forms\EditUserPersonalDetailsForm;
use App\AdminModule\Forms\EditUserSeminarForm;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\Enums\PaymentType;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Services\ApplicationService;
use App\Services\ExcelExportService;
use App\Services\PdfExportService;
use Nette\Application\UI\Form;


/**
 * Presenter obsluhující správu uživatelů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class UsersPresenter extends AdminBasePresenter
{
    protected $resource = Resource::USERS;
    
    /**
     * @var IUsersGridControlFactory
     * @inject
     */
    public $usersGridControlFactory;

    /**
     * @var AddLectorForm
     * @inject
     */
    public $addLectorFormFactory;

    /**
     * @var EditUserPersonalDetailsForm
     * @inject
     */
    public $editUserPersonalDetailsFormFactory;

    /**
     * @var EditUserSeminarForm
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
     * @throws \Nette\Application\AbortException
     */
    public function startup()
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);

        $this->template->results = [];
        $this->template->editPersonalDetails = FALSE;
        $this->template->editSeminar = FALSE;
        $this->template->editPayment = FALSE;
    }

    /**
     * @param $id
     */
    public function renderDetail($id)
    {
        $user = $this->userRepository->findById($id);

        $this->template->sidebarVisible = TRUE;
        $this->template->detailUser = $user;

        $this->template->customInputs = $this->customInputRepository->findAllOrderedByPosition();
        $this->template->customInputTypeText = CustomInput::TEXT;
        $this->template->customInputTypeCheckbox = CustomInput::CHECKBOX;
        $this->template->customInputTypeSelect = CustomInput::SELECT;

        $this->template->roleAdminName = $this->roleRepository->findBySystemName(Role::ADMIN)->getName();
        $this->template->roleOrganizerName = $this->roleRepository->findBySystemName(Role::ORGANIZER)->getName();

        $this->template->paymentMethodCash = PaymentType::CASH;
        $this->template->paymentMethodBank = PaymentType::BANK;

        $this->template->registered = !$user->isInRole($this->roleRepository->findBySystemName(Role::NONREGISTERED));
    }

    /**
     * Zpracuje fulltext vyhledávání v displayName uživatelů.
     * @param $text
     */
    public function handleSearch($text)
    {
        $this->template->results = $this->userRepository->findNamesByLikeDisplayNameOrderedByDisplayName($text);
        $this->redrawControl('results');
    }

    /**
     * Zobrazí formulář pro editaci osobních údajů uživatele.
     * @throws \Nette\Application\AbortException
     */
    public function handleEditPersonalDetails()
    {
        $this->template->editPersonalDetails = TRUE;

        if ($this->isAjax()) {
            $this->redrawControl('userDetail');
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Zobrazí formulář pro editaci údajů o účasti uživatele na semináři.
     * @throws \Nette\Application\AbortException
     */
    public function handleEditSeminar()
    {
        $this->template->editSeminar = TRUE;

        if ($this->isAjax()) {
            $this->redrawControl('userDetail');
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Zobrazí formulář pro editaci údajů o platbě uživatele.
     * @throws \Nette\Application\AbortException
     */
    public function handleEditPayment()
    {
        $this->template->editPayment = TRUE;

        if ($this->isAjax()) {
            $this->redrawControl('userDetail');
        } else {
            $this->redirect('this');
        }
    }

    /**
     * @throws \Throwable
     */
    public function handleCancelRegistration()
    {
        $user = $this->userRepository->findById($this->getParameter('id'));
        $loggedUser = $this->userRepository->findById($this->user->id);

        $this->applicationService->cancelRegistration($user, $loggedUser);

        $this->flashMessage('admin.users.users_registration_canceled', 'success');
        $this->redirect('this');
    }

    protected function createComponentUsersGrid()
    {
        return $this->usersGridControlFactory->create();
    }

    protected function createComponentAddLectorForm()
    {
        $form = $this->addLectorFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($form['cancel']->isSubmittedBy()) {
                $this->redirect('Users:default');
            } else {
                $this->flashMessage('admin.users.users_saved', 'success');
                $this->redirect('Users:default');
            }
        };

        return $form;
    }

    protected function createComponentEditUserPersonalDetailsForm()
    {
        $form = $this->editUserPersonalDetailsFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($form['cancel']->isSubmittedBy()) {
                $this->redirect('this');
            } else {
                $this->flashMessage('admin.users.users_saved', 'success');
                $this->redirect('this');
            }
        };

        return $form;
    }

    protected function createComponentEditUserSeminarForm()
    {
        $form = $this->editUserSeminarFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if (!$form['cancel']->isSubmittedBy())
                $this->flashMessage('admin.users.users_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentApplicationsGrid()
    {
        return $this->applicationsGridControlFactory->create();
    }
}
