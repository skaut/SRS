<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Commands\SetSettingDateTimeValue;
use App\Model\Settings\Queries\SettingDateTimeValueQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Nette\Application\UI\Form;
use Nextras\FormComponents\Controls\DateTimeControl;
use Nextras\FormsRendering\Renderers\Bs5FormRenderer;
use stdClass;
use Throwable;

use function assert;

/**
 * Formulář pro nastavení vstupenek.
 */
class TicketsFormFactory
{
    public function __construct(private BaseFormFactory $baseFormFactory, private CommandBus $commandBus, private QueryBus $queryBus)
    {
    }

    /**
     * Vytvoří formulář.
     *
     * @throws Throwable
     */
    public function create(): Form
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        assert($renderer instanceof Bs5FormRenderer);
        $renderer->wrappers['control']['container'] = 'div class="col-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-5 col-form-label"';

        $ticketsAllowedCheckbox = $form->addCheckbox('ticketsAllowed', 'admin.configuration.tickets.tickets.allowed');
        $ticketsAllowedCheckbox->addCondition($form::EQUAL, true)
            ->toggle('tickets-from');

        $ticketsFromDateTime = new DateTimeControl('admin.configuration.tickets.tickets.from');
        $ticketsFromDateTime->setOption('id', 'tickets-from');
        $form->addComponent($ticketsFromDateTime, 'ticketsFrom');

        $form->addSubmit('submit', 'admin.common.save');

        $ticketsFrom = $this->queryBus->handle(new SettingDateTimeValueQuery(Settings::TICKETS_FROM));

        $form->setDefaults([
            'ticketsAllowed' => $ticketsFrom !== null,
            'ticketsFrom' => $ticketsFrom,
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
        if ($values->ticketsAllowed) {
            $this->commandBus->handle(new SetSettingDateTimeValue(Settings::TICKETS_FROM, $values->ticketsFrom));
        } else {
            $this->commandBus->handle(new SetSettingDateTimeValue(Settings::TICKETS_FROM, null));
        }
    }
}
