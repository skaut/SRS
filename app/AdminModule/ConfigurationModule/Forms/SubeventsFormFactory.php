<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Commands\SetSettingBoolValue;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Nette;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Formulář pro nastavení podakcí.
 */
class SubeventsFormFactory
{
    use Nette\SmartObject;

    public function __construct(
        private BaseFormFactory $baseFormFactory,
        private CommandBus $commandBus,
        private QueryBus $queryBus,
    ) {
    }

    /**
     * Vytvoří formulář.
     *
     * @throws Throwable
     */
    public function create(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addCheckbox('isAllowedAddSubeventsAfterPayment', 'admin.configuration.is_allowed_add_subevents_after_payment');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'isAllowedAddSubeventsAfterPayment' => $this->queryBus->handle(new SettingStringValueQuery(Settings::IS_ALLOWED_ADD_SUBEVENTS_AFTER_PAYMENT)),
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
        $this->commandBus->handle(new SetSettingBoolValue(Settings::IS_ALLOWED_ADD_SUBEVENTS_AFTER_PAYMENT, $values->isAllowedAddSubeventsAfterPayment));
    }
}
