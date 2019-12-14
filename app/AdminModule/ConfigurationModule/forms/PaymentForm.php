<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Enums\MaturityType;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\User\UserRepository;
use App\Services\SettingsService;
use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use Nextras\Forms\Rendering\Bs3FormRenderer;
use stdClass;
use Throwable;

/**
 * Formulář pro nastavení platby.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class PaymentForm extends UI\Control
{
    /**
     * Událost při uložení formuláře.
     * @var callable
     */
    public $onSave;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var SettingsService */
    private $settingsService;

    /** @var UserRepository */
    private $userRepository;


    public function __construct(BaseForm $baseForm, SettingsService $settingsService, UserRepository $userRepository)
    {
        parent::__construct();

        $this->baseFormFactory = $baseForm;
        $this->settingsService = $settingsService;
        $this->userRepository  = $userRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/payment_form.latte');
        $this->template->render();
    }

    /**
     * Vytvoří formulář.
     * @throws SettingsException
     * @throws Throwable
     */
    public function createComponentForm() : Form
    {
        $form = $this->baseFormFactory->create();

        /** @var Bs3FormRenderer $renderer */
        $renderer                                   = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addText('accountNumber', 'admin.configuration.account_number')
            ->addRule(Form::FILLED, 'admin.configuration.account_number_empty')
            ->addRule(Form::PATTERN, 'admin.configuration.account_number_format', '^(\d{1,6}-|)\d{2,10}\/\d{4}$');

        $form->addText('variableSymbolCode', 'admin.configuration.variable_symbol_code')
            ->addCondition(Form::FILLED)
            ->addRule(Form::PATTERN, 'admin.configuration.variable_symbol_code_format', '^\d{0,4}$');

        $maturityTypeSelect = $form->addSelect('maturityType', 'admin.configuration.maturity_type', $this->prepareMaturityTypeOptions());
        $maturityTypeSelect->addCondition($form::EQUAL, MaturityType::DATE)
            ->toggle('maturity-date')
            ->toggle('maturity-reminder')
            ->toggle('cancel-registration-after-maturity');
        $maturityTypeSelect->addCondition($form::EQUAL, MaturityType::DAYS)
            ->toggle('maturity-days')
            ->toggle('maturity-reminder')
            ->toggle('cancel-registration-after-maturity');
        $maturityTypeSelect->addCondition($form::EQUAL, MaturityType::WORK_DAYS)
            ->toggle('maturity-work-days')
            ->toggle('maturity-reminder')
            ->toggle('cancel-registration-after-maturity');

        $form->addDatePicker('maturityDate', 'admin.configuration.maturity_date')
            ->setOption('id', 'maturity-date');

        $form->addText('maturityDays', 'admin.configuration.maturity_days')
            ->setOption('id', 'maturity-days')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.configuration.maturity_days_format');

        $form->addText('maturityWorkDays', 'admin.configuration.maturity_work_days')
            ->setOption('id', 'maturity-work-days')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.configuration.maturity_work_days_format');

        $form->addText('maturityReminder', 'admin.configuration.maturity_reminder')
            ->setOption('id', 'maturity-reminder')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.configuration.maturity_reminder_format');

        $form->addText('cancelRegistrationAfterMaturity', 'admin.configuration.cancel_registration_after_maturity')
            ->setOption('id', 'cancel-registration-after-maturity')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.configuration.cancel_registration_after_maturity_format');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'accountNumber' => $this->settingsService->getValue(Settings::ACCOUNT_NUMBER),
            'variableSymbolCode' => $this->settingsService->getValue(Settings::VARIABLE_SYMBOL_CODE),
            'maturityType' => $this->settingsService->getValue(Settings::MATURITY_TYPE),
            'maturityDate' => $this->settingsService->getDateValue(Settings::MATURITY_DATE),
            'maturityDays' => $this->settingsService->getIntValue(Settings::MATURITY_DAYS),
            'maturityWorkDays' => $this->settingsService->getIntValue(Settings::MATURITY_WORK_DAYS),
            'maturityReminder' => $this->settingsService->getIntValue(Settings::MATURITY_REMINDER),
            'cancelRegistrationAfterMaturity' => $this->settingsService->getIntValue(Settings::CANCEL_REGISTRATION_AFTER_MATURITY),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @throws SettingsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        $this->settingsService->setValue(Settings::ACCOUNT_NUMBER, $values->accountNumber);
        $this->settingsService->setValue(Settings::VARIABLE_SYMBOL_CODE, $values->variableSymbolCode);
        $this->settingsService->setValue(Settings::MATURITY_TYPE, $values->maturityType);

        if (property_exists($values, 'maturityDate')) {
            $this->settingsService->setDateValue(Settings::MATURITY_DATE, $values->maturityDate ?: (new DateTime())->setTime(0, 0));
        }

        if (property_exists($values, 'maturityDays')) {
            $this->settingsService->setIntValue(
                Settings::MATURITY_DAYS,
                $values->maturityDays !== '' ? $values->maturityDays : 0
            );
        }

        if (property_exists($values, 'maturityWorkDays')) {
            $this->settingsService->setIntValue(
                Settings::MATURITY_WORK_DAYS,
                $values->maturityWorkDays !== '' ? $values->maturityWorkDays : 0
            );
        }

        if (property_exists($values, 'maturityReminder')) {
            $this->settingsService->setIntValue(
                Settings::MATURITY_REMINDER,
                $values->maturityReminder !== '' ? $values->maturityReminder : null
            );
        }

        if (property_exists($values, 'cancelRegistrationAfterMaturity')) {
            $this->settingsService->setIntValue(
                Settings::CANCEL_REGISTRATION_AFTER_MATURITY,
                $values->cancelRegistrationAfterMaturity !== '' ? $values->cancelRegistrationAfterMaturity : null
            );
        }

        $this->onSave($this);
    }

    /**
     * Vrátí způsoby výpočtu splatnosti jako možnosti pro select.
     * @return string[]
     */
    private function prepareMaturityTypeOptions() : array
    {
        $options = [];
        foreach (MaturityType::$types as $type) {
            $options[$type] = 'common.maturity_type.' . $type;
        }
        return $options;
    }
}
