<?php

namespace App\AdminModule\Presenters;


use App\AdminModule\Components\IUsersGridControlFactory;
use App\AdminModule\Forms\EditUserForm;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\Enums\PaymentType;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\Settings;
use App\Model\User\CustomInputValue\CustomInputValueRepository;
use App\Services\ExcelExportService;
use App\Services\PdfExportService;
use Nette\Application\UI\Form;
use Nette\Http\Session;

class UsersPresenter extends AdminBasePresenter
{
    protected $resource = Resource::USERS;

    /**
     * @var IUsersGridControlFactory
     * @inject
     */
    public $usersGridControlFactory;

    /**
     * @var EditUserForm
     * @inject
     */
    public $editUserFormFactory;

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

        $this->template->variableSymbolCode = $this->settingsRepository->getValue('variable_symbol_code');
    }

    public function renderEdit($id) {
        $this->template->sidebarVisible = true;
        $this->template->editedUser = $this->userRepository->findById($id);
    }

    public function handleSearch($text)
    {
        $this->template->results = $this->userRepository->findNamesByLikeDisplayNameOrderedByDisplayName($text);
        $this->redrawControl('results');
    }

    public function actionGeneratePaymentProofCash($id) {
        $user = $this->userRepository->findById($id);
        $this->pdfExportService->generatePaymentProof($user, "prijmovy-pokladni-doklad.pdf");
    }

    public function actionGeneratePaymentProofBank($id) {
        $user = $this->userRepository->findById($id);
        $this->pdfExportService->generatePaymentProof($user, "potvrzeni-o-prijeti-platby.pdf");
    }

    protected function createComponentUsersGrid($name)
    {
        return $this->usersGridControlFactory->create();
    }

    protected function createComponentEditUserForm($name)
    {
        $form = $this->editUserFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.users.users_saved', 'success');

            if ($form['submitAndContinue']->isSubmittedBy()) {
                $id = $values['id'];
                $this->redirect('Users:edit', ['id' => $id]);
            }
            else
                $this->redirect('Users:default');
        };

        return $form;
    }
}