<?php

namespace App\AdminModule\Forms;

use App\Model\Enums\PaymentType;
use App\Model\Mailing\Mail;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\MailService;
use Doctrine\Common\Collections\ArrayCollection;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro úpravu údajů o platbě uživatele.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class EditUserPaymentForm extends Nette\Object
{
    /**
     * Upravovaný uživatel.
     * @var User
     */
    private $user;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var MailService */
    private $mailService;


    /**
     * EditUserPaymentForm constructor.
     * @param BaseForm $baseFormFactory
     * @param UserRepository $userRepository
     * @param SettingsRepository $settingsRepository
     * @param MailService $mailService
     */
    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository,
                                SettingsRepository $settingsRepository, MailService $mailService)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->settingsRepository = $settingsRepository;
        $this->mailService = $mailService;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @return Form
     */
    public function create($id)
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addText('variableSymbol', 'admin.users.users_variable_symbol')
            ->addRule(Form::FILLED)
            ->addRule(Form::PATTERN, 'admin.users.users_edit_variable_symbol_format', '^\d{1,10}$');

        $form->addSelect('paymentMethod', 'admin.users.users_payment_method', $this->preparePaymentMethodOptions());

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

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     */
    public function processForm(Form $form, \stdClass $values)
    {
        if (!$form['cancel']->isSubmittedBy()) {
            $oldPaymentDate = $this->user->getPaymentDate();

            $this->user->setVariableSymbol($values['variableSymbol']);
            $this->user->setPaymentMethod($values['paymentMethod']);
            $this->user->setPaymentDate($values['paymentDate']);
            $this->user->setIncomeProofPrintedDate($values['incomeProofPrintedDate']);

            $this->userRepository->save($this->user);

            if ($values['paymentDate'] !== NULL && $oldPaymentDate === NULL) {
                $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$this->user]), '', Template::PAYMENT_CONFIRMED, [
                    TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME)
                ]);
            }
        }
    }

    /**
     * Vrátí platební metody jako možnosti pro select.
     * @return array
     */
    private function preparePaymentMethodOptions()
    {
        $options = [];
        $options[''] = '';
        foreach (PaymentType::$types as $type)
            $options[$type] = 'common.payment.' . $type;
        return $options;
    }
}
