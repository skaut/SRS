<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Commands\SetSettingDateValue;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingDateValueQuery;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Nette;
use Nette\Application\UI\Form;
use Nextras\FormComponents\Controls\DateControl;
use stdClass;
use Throwable;

/**
 * Formulář pro nastavení přihlášky.
 */
class ApplicationFormFactory
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
     * @throws Throwable
     */
    public function create(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addTextArea('applicationAgreement', 'admin.configuration.application_agreement')
            ->setHtmlAttribute('rows', 5);

        $editCustomInputsToDate = new DateControl('admin.configuration.application_edit_custom_inputs_to');
        $editCustomInputsToDate->addRule(Form::FILLED, 'admin.configuration.application_edit_custom_inputs_to_empty');
        $form->addComponent($editCustomInputsToDate, 'editCustomInputsTo');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'applicationAgreement' => $this->queryBus->handle(new SettingStringValueQuery(Settings::APPLICATION_AGREEMENT)),
            'editCustomInputsTo' => $this->queryBus->handle(new SettingDateValueQuery(Settings::EDIT_CUSTOM_INPUTS_TO)),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values): void
    {
        $this->commandBus->handle(new SetSettingStringValue(Settings::APPLICATION_AGREEMENT, $values->applicationAgreement));
        $this->commandBus->handle(new SetSettingDateValue(Settings::EDIT_CUSTOM_INPUTS_TO, $values->editCustomInputsTo));
    }
}
