<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Enums\MaturityType;
use App\Model\Settings\Commands\SetSettingDateValue;
use App\Model\Settings\Commands\SetSettingIntValue;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingDateValueQuery;
use App\Model\Settings\Queries\SettingIntValueQuery;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use App\Services\QueryBus;
use DateTimeImmutable;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use Nextras\FormComponents\Controls\DateControl;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use stdClass;
use Throwable;

use function assert;
use function property_exists;

/**
 * Formulář pro nastavení platby
 */
class PaymentForm extends UI\Control
{
    /**
     * Událost při uložení formuláře
     *
     * @var callable[]
     */
    public array $onSave = [];

    public function __construct(private BaseFormFactory $baseFormFactory, private CommandBus $commandBus, private QueryBus $queryBus)
    {
    }

    /**
     * Vykreslí komponentu
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/payment_form.latte');
        $this->template->render();
    }

    /**
     * Vytvoří formulář
     *
     * @throws Throwable
     */
    public function createComponentForm(): Form
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        assert($renderer instanceof Bs4FormRenderer);
        $renderer->wrappers['control']['container'] = 'div class="col-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-5 col-form-label"';

        $form->addText('accountNumber', 'admin.configuration.payment.payment.account_number')
            ->addRule(Form::FILLED, 'admin.configuration.payment.payment.account_number_empty')
            ->addRule(Form::PATTERN, 'admin.configuration.payment.payment.account_number_format', '^(\d{1,6}-|)\d{2,10}\/\d{4}$');

        $form->addText('variableSymbolCode', 'admin.configuration.payment.payment.variable_symbol_code')
            ->addCondition(Form::FILLED)
            ->addRule(Form::PATTERN, 'admin.configuration.payment.payment.variable_symbol_code_format', '^\d{0,4}$');

        $maturityTypeSelect = $form->addSelect('maturityType', 'admin.configuration.payment.payment.maturity_type', $this->prepareMaturityTypeOptions());
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

        $maturityDateDate = new DateControl('admin.configuration.payment.payment.maturity_date');
        $maturityDateDate->setOption('id', 'maturity-date');
        $form->addComponent($maturityDateDate, 'maturityDate');

        $form->addText('maturityDays', 'admin.configuration.payment.payment.maturity_days')
            ->setOption('id', 'maturity-days')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.configuration.payment.payment.maturity_days_format');

        $form->addText('maturityWorkDays', 'admin.configuration.payment.payment.maturity_work_days')
            ->setOption('id', 'maturity-work-days')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.configuration.payment.payment.maturity_work_days_format');

        $form->addText('maturityReminder', 'admin.configuration.payment.payment.maturity_reminder')
            ->setOption('id', 'maturity-reminder')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.configuration.payment.payment.maturity_reminder_format');

        $form->addText('cancelRegistrationAfterMaturity', 'admin.configuration.payment.payment.cancel_registration_after_maturity')
            ->setOption('id', 'cancel-registration-after-maturity')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.configuration.payment.payment.cancel_registration_after_maturity_format');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'accountNumber' => $this->queryBus->handle(new SettingStringValueQuery(Settings::ACCOUNT_NUMBER)),
            'variableSymbolCode' => $this->queryBus->handle(new SettingStringValueQuery(Settings::VARIABLE_SYMBOL_CODE)),
            'maturityType' => $this->queryBus->handle(new SettingStringValueQuery(Settings::MATURITY_TYPE)),
            'maturityDate' => $this->queryBus->handle(new SettingDateValueQuery(Settings::MATURITY_DATE)),
            'maturityDays' => $this->queryBus->handle(new SettingIntValueQuery(Settings::MATURITY_DAYS)),
            'maturityWorkDays' => $this->queryBus->handle(new SettingIntValueQuery(Settings::MATURITY_WORK_DAYS)),
            'maturityReminder' => $this->queryBus->handle(new SettingIntValueQuery(Settings::MATURITY_REMINDER)),
            'cancelRegistrationAfterMaturity' => $this->queryBus->handle(new SettingIntValueQuery(Settings::CANCEL_REGISTRATION_AFTER_MATURITY)),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values): void
    {
        $this->commandBus->handle(new SetSettingStringValue(Settings::ACCOUNT_NUMBER, $values->accountNumber));
        $this->commandBus->handle(new SetSettingStringValue(Settings::VARIABLE_SYMBOL_CODE, $values->variableSymbolCode));
        $this->commandBus->handle(new SetSettingStringValue(Settings::MATURITY_TYPE, $values->maturityType));

        if (property_exists($values, 'maturityDate')) {
            $this->commandBus->handle(new SetSettingDateValue(Settings::MATURITY_DATE, $values->maturityDate ?: (new DateTimeImmutable())->setTime(0, 0)));
        }

        if (property_exists($values, 'maturityDays')) {
            $this->commandBus->handle(new SetSettingIntValue(
                Settings::MATURITY_DAYS,
                $values->maturityDays !== '' ? $values->maturityDays : 0
            ));
        }

        if (property_exists($values, 'maturityWorkDays')) {
            $this->commandBus->handle(new SetSettingIntValue(
                Settings::MATURITY_WORK_DAYS,
                $values->maturityWorkDays !== '' ? $values->maturityWorkDays : 0
            ));
        }

        if (property_exists($values, 'maturityReminder')) {
            $this->commandBus->handle(new SetSettingIntValue(
                Settings::MATURITY_REMINDER,
                $values->maturityReminder !== '' ? $values->maturityReminder : null
            ));
        }

        if (property_exists($values, 'cancelRegistrationAfterMaturity')) {
            $this->commandBus->handle(new SetSettingIntValue(
                Settings::CANCEL_REGISTRATION_AFTER_MATURITY,
                $values->cancelRegistrationAfterMaturity !== '' ? $values->cancelRegistrationAfterMaturity : null
            ));
        }

        $this->onSave($this);
    }

    /**
     * Vrátí způsoby výpočtu splatnosti jako možnosti pro select
     *
     * @return string[]
     */
    private function prepareMaturityTypeOptions(): array
    {
        $options = [];
        foreach (MaturityType::$types as $type) {
            $options[$type] = 'common.maturity_type.' . $type;
        }

        return $options;
    }
}
