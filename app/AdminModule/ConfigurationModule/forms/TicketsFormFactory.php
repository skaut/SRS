<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Services\SettingsService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nextras\Forms\Rendering\Bs3FormRenderer;
use stdClass;
use Throwable;

/**
 * Formulář pro nastavení vstupenek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TicketsFormFactory
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var SettingsService */
    private $settingsService;


    public function __construct(BaseFormFactory $baseForm, SettingsService $settingsService)
    {
        $this->baseFormFactory = $baseForm;
        $this->settingsService = $settingsService;
    }

    /**
     * Vytvoří formulář.
     * @throws SettingsException
     * @throws Throwable
     */
    public function create() : BaseForm
    {
        $form = $this->baseFormFactory->create();

        /** @var Bs3FormRenderer $renderer */
        $renderer                                   = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-sm-5 col-xs-5 control-label"';

        $ticketsAllowedCheckbox = $form->addCheckbox('ticketsAllowed', 'admin.configuration.payment.tickets.allowed');
        $ticketsAllowedCheckbox->addCondition($form::EQUAL, true)
            ->toggle('tickets-from');

        $form->addDateTimePicker('ticketsFrom', 'admin.configuration.payment.tickets.from')
            ->setOption('id', 'tickets-from');

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
     * @throws SettingsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function processForm(BaseForm $form, stdClass $values) : void
    {
        if ($values->ticketsAllowed) {
            $this->settingsService->setDateTimeValue(Settings::TICKETS_FROM, $values->ticketsFrom);
        } else {
            $this->settingsService->setDateTimeValue(Settings::TICKETS_FROM, null);
        }
    }
}