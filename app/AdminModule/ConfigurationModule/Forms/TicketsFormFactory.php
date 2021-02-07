<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Settings;
use App\Services\ISettingsService;
use Nette\Application\UI\Form;
use Nextras\FormComponents\Controls\DateTimeControl;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use stdClass;
use Throwable;

use function assert;

/**
 * Formulář pro nastavení vstupenek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TicketsFormFactory
{
    private BaseFormFactory $baseFormFactory;

    private ISettingsService $settingsService;

    public function __construct(BaseFormFactory $baseForm, ISettingsService $settingsService)
    {
        $this->baseFormFactory = $baseForm;
        $this->settingsService = $settingsService;
    }

    /**
     * Vytvoří formulář.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function create(): Form
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        assert($renderer instanceof Bs4FormRenderer);
        $renderer->wrappers['control']['container'] = 'div class="col-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-5 col-form-label"';

        $ticketsAllowedCheckbox = $form->addCheckbox('ticketsAllowed', 'admin.configuration.payment.tickets.allowed');
        $ticketsAllowedCheckbox->addCondition($form::EQUAL, true)
            ->toggle('tickets-from');

        $ticketsFromDateTime = new DateTimeControl('admin.configuration.payment.tickets.from');
        $ticketsFromDateTime->setOption('id', 'tickets-from');
        $form->addComponent($ticketsFromDateTime, 'ticketsFrom');

        $form->addSubmit('submit', 'admin.common.save');

        $ticketsFrom = $this->settingsService->getDateTimeValue(Settings::TICKETS_FROM);

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
     * @throws SettingsException
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values): void
    {
        if ($values->ticketsAllowed) {
            $this->settingsService->setDateTimeValue(Settings::TICKETS_FROM, $values->ticketsFrom);
        } else {
            $this->settingsService->setDateTimeValue(Settings::TICKETS_FROM, null);
        }
    }
}
