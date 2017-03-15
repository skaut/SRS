<?php

namespace App\AdminModule\Forms;


use App\Model\Enums\PaymentType;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Nette;
use Nette\Application\UI\Form;

class EditUserPaymentForm extends Nette\Object
{
    /** @var User */
    private $user;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var SettingsRepository */
    private $settingsRepository;

    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository,
                                SettingsRepository $settingsRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->settingsRepository = $settingsRepository;
    }

    public function create($id)
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addText('variableSymbol', 'admin.users.users_variable_symbol')
            ->addRule(Form::FILLED)
            ->addRule(Form::PATTERN, 'admin.users.users_edit_variable_symbol_format', '^\d{8}$');

        $form->addSelect('paymentMethod', 'Platební metoda', $this->preparePaymentMethodOptions());

        $form->addDatePicker('paymentDate', 'admin.users.users_payment_date');

        $form->addDatePicker('incomeProofPrintedDate', 'admin.users.users_income_proof_printed_date');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setAttribute('class', 'btn btn-warning');


        $form->setDefaults([
            'id' => $id,
            'variableSymbol' => $this->user->getVariableSymbol(),
            'paymentMethod' => $this->user->getPaymentMethod(),
            'paymentDate' => $this->user->getPaymentDate(),
            'incomeProofPrintedDate' => $this->user->getIncomeProofPrintedDate(),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values)
    {
        if (!$form['cancel']->isSubmittedBy()) {
            $this->user->setVariableSymbol($values['variableSymbol']);

            $this->user->setPaymentMethod($values['paymentMethod']);

            $this->user->setPaymentDate($values['paymentDate']);

            $this->user->setIncomeProofPrintedDate($values['incomeProofPrintedDate']);

            $this->userRepository->save($this->user);
        }
    }

    private function preparePaymentMethodOptions()
    {
        $options = [];
        $options[''] = '';
        foreach (PaymentType::$types as $type)
            $options[$type] = 'common.payment.' . $type;
        return $options;
    }
}
