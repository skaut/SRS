<?php

namespace App\AdminModule\Presenters;


use App\AdminModule\Components\IUsersGridControlFactory;
use App\AdminModule\Forms\EditUserPaymentForm;
use App\AdminModule\Forms\EditUserSeminarForm;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\Enums\PaymentType;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Services\ExcelExportService;
use App\Services\PdfExportService;
use Nette\Application\UI\Form;


class UsersPresenter extends AdminBasePresenter
{
    protected $resource = Resource::USERS;

    /**
     * @var IUsersGridControlFactory
     * @inject
     */
    public $usersGridControlFactory;

    /**
     * @var EditUserSeminarForm
     * @inject
     */
    public $editUserSeminarFormFactory;

    /**
     * @var EditUserPaymentForm
     * @inject
     */
    public $editUserPaymentFormFactory;

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


    public function startup()
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);

        $this->template->results = [];
        $this->template->editSeminar = false;
        $this->template->editPayment = false;
    }

    public function renderDetail($id) {
        $this->template->sidebarVisible = true;
        $this->template->detailUser = $this->userRepository->findById($id);

        $this->template->customInputs = $this->customInputRepository->findAllOrderedByPosition();
        $this->template->customInputTypeText = CustomInput::TEXT;
        $this->template->customInputTypeCheckbox = CustomInput::CHECKBOX;

        $this->template->roleAdminName = $this->roleRepository->findBySystemName(Role::ADMIN)->getName();
        $this->template->roleOrganizerName = $this->roleRepository->findBySystemName(Role::ORGANIZER)->getName();

        $this->template->paymentMethodCash = PaymentType::CASH;
        $this->template->paymentMethodBank = PaymentType::BANK;
    }

    public function handleSearch($text)
    {
        $this->template->results = $this->userRepository->findNamesByLikeDisplayNameOrderedByDisplayName($text);
        $this->redrawControl('results');
    }

    public function handleEditSeminar() {
        $this->template->editSeminar = true;

        if ($this->isAjax()) {
            $this->redrawControl('userDetail');
        }
        else {
            $this->redirect('this');
        }
    }

    public function handleEditPayment() {
        $this->template->editPayment = true;

        if ($this->isAjax()) {
            $this->redrawControl('userDetail');
        }
        else {
            $this->redirect('this');
        }
    }

    public function actionGeneratePaymentProofCash($id) {
        $user = $this->userRepository->findById($id);
        if (!$user->getIncomeProofPrintedDate()) {
            $user->setIncomeProofPrintedDate(new \DateTime());
            $this->userRepository->save($user);
        }
        $this->pdfExportService->generatePaymentProof($user, "prijmovy-pokladni-doklad.pdf");
    }

    public function actionGeneratePaymentProofBank($id) {
        $user = $this->userRepository->findById($id);
        if (!$user->getIncomeProofPrintedDate()) {
            $user->setIncomeProofPrintedDate(new \DateTime());
            $this->userRepository->save($user);
        }
        $this->pdfExportService->generatePaymentProof($user, "potvrzeni-o-prijeti-platby.pdf");
    }

    protected function createComponentUsersGrid()
    {
        return $this->usersGridControlFactory->create();
    }

    protected function createComponentEditUserSeminarForm()
    {
        $form = $this->editUserSeminarFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($form['cancel']->isSubmittedBy()) {
                $this->redirect('this');
            }
            else {
                $this->flashMessage('admin.users.users_saved', 'success');
                $this->redirect('this');
            }
        };

        return $form;
    }

    protected function createComponentEditUserPaymentForm()
    {
        $form = $this->editUserPaymentFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($form['cancel']->isSubmittedBy()) {
                $this->redirect('this');
            }
            else {
                $this->flashMessage('admin.users.users_saved', 'success');
                $this->redirect('this');
            }
        };

        return $form;
    }
}