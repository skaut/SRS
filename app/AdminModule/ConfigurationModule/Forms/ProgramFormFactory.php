<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Enums\CalendarView;
use App\Model\Enums\ProgramRegistrationType;
use App\Model\Settings\Commands\SetSettingBoolValue;
use App\Model\Settings\Commands\SetSettingDateTimeValue;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Queries\SettingDateTimeValueQuery;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;
use Nextras\FormComponents\Controls\DateTimeControl;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use stdClass;
use Throwable;

use function assert;

/**
 * Formulář pro nastavení programu.
 */
class ProgramFormFactory
{
    use Nette\SmartObject;

    private BaseFormFactory $baseFormFactory;

    private CommandBus $commandBus;

    private QueryBus $queryBus;

    public function __construct(BaseFormFactory $baseForm, CommandBus $commandBus, QueryBus $queryBus)
    {
        $this->baseFormFactory = $baseForm;
        $this->commandBus      = $commandBus;
        $this->queryBus        = $queryBus;
    }

    /**
     * Vytvoří formulář.
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function create(): Form
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        assert($renderer instanceof Bs4FormRenderer);
        $renderer->wrappers['control']['container'] = 'div class="col-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-5 col-form-label"';

        $registerProgramsTypeSelect = $form->addSelect(
            'registerProgramsType',
            'admin.configuration.register_programs_type',
            $this->prepareRegisterProgramsTypeOptions()
        );
        $registerProgramsTypeSelect
            ->addCondition($form::EQUAL, ProgramRegistrationType::ALLOWED_FROM_TO)
            ->toggle('register-programs-from')
            ->toggle('register-programs-to');

        $registerProgramsFromDateTime = new DateTimeControl('admin.configuration.register_programs_from');
        $registerProgramsFromDateTime->setOption('id', 'register-programs-from');
        $form->addComponent($registerProgramsFromDateTime, 'registerProgramsFrom');

        $registerProgramsToDateTime = new DateTimeControl('admin.configuration.register_programs_to');
        $registerProgramsToDateTime->setOption('id', 'register-programs-to');
        $form->addComponent($registerProgramsToDateTime, 'registerProgramsTo');

        $form->addCheckbox('isAllowedRegisterProgramsBeforePayment', 'admin.configuration.is_allowed_register_programs_before_payment');

        $registerProgramsFromDateTime
            ->addCondition(Form::FILLED)
            ->addRule([$this, 'validateRegisterProgramsFrom'], 'admin.configuration.register_programs_from_after_to', [$registerProgramsFromDateTime, $registerProgramsToDateTime]);

        $registerProgramsToDateTime
            ->addCondition(Form::FILLED)
            ->addRule([$this, 'validateRegisterProgramsTo'], 'admin.configuration.register_programs_to_before_from', [$registerProgramsToDateTime, $registerProgramsFromDateTime]);

        $form->addCheckbox('isAllowedAddBlock', 'admin.configuration.is_allowed_add_block');

        $form->addCheckbox('isAllowedModifySchedule', 'admin.configuration.is_allowed_modify_schedule');

        $form->addSelect('scheduleInitialView', 'admin.configuration.schedule_initial_view', CalendarView::getCalendarViewsOptions());

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'isAllowedAddBlock' => $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_ADD_BLOCK)),
            'isAllowedModifySchedule' => $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_MODIFY_SCHEDULE)),
            'registerProgramsType' => $this->queryBus->handle(new SettingStringValueQuery(Settings::REGISTER_PROGRAMS_TYPE)),
            'registerProgramsFrom' => $this->queryBus->handle(new SettingDateTimeValueQuery(Settings::REGISTER_PROGRAMS_FROM)),
            'registerProgramsTo' => $this->queryBus->handle(new SettingDateTimeValueQuery(Settings::REGISTER_PROGRAMS_TO)),
            'isAllowedRegisterProgramsBeforePayment' => $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT)),
            'scheduleInitialView' => $this->queryBus->handle(new SettingStringValueQuery(Settings::SCHEDULE_INITIAL_VIEW)),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values): void
    {
        $this->commandBus->handle(new SetSettingBoolValue(Settings::IS_ALLOWED_ADD_BLOCK, $values->isAllowedAddBlock));
        $this->commandBus->handle(new SetSettingBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE, $values->isAllowedModifySchedule));
        $this->commandBus->handle(new SetSettingStringValue(Settings::REGISTER_PROGRAMS_TYPE, $values->registerProgramsType));
        $this->commandBus->handle(new SetSettingBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, $values->isAllowedRegisterProgramsBeforePayment));
        $this->commandBus->handle(new SetSettingDateTimeValue(Settings::REGISTER_PROGRAMS_FROM, $values->registerProgramsFrom));
        $this->commandBus->handle(new SetSettingDateTimeValue(Settings::REGISTER_PROGRAMS_TO, $values->registerProgramsTo));
        $this->commandBus->handle(new SetSettingStringValue(Settings::SCHEDULE_INITIAL_VIEW, $values->scheduleInitialView));
    }

    /**
     * Ověří, že otevření zapisování programů je dříve než uzavření.
     *
     * @param DateTime[]|null[] $args
     */
    public function validateRegisterProgramsFrom(DateTimeControl $field, array $args): bool
    {
        if ($args[0] === null || $args[1] === null) {
            return true;
        }

        return $args[0] < $args[1];
    }

    /**
     * Ověří, že uzavření zapisování programů je později než otevření.
     *
     * @param DateTime[]|null[] $args
     */
    public function validateRegisterProgramsTo(DateTimeControl $field, array $args): bool
    {
        if ($args[0] === null || $args[1] === null) {
            return true;
        }

        return $args[0] > $args[1];
    }

    /**
     * Vrátí stavy registrace programů.
     *
     * @return string[]
     */
    private function prepareRegisterProgramsTypeOptions(): array
    {
        $options = [];
        foreach (ProgramRegistrationType::$types as $type) {
            $options[$type] = 'common.register_programs_type.' . $type;
        }

        return $options;
    }
}
