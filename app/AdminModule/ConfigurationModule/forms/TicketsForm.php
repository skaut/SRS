<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette\Application\UI\Form;

/**
 * Formulář pro nastavení vstupenek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TicketsForm
{
    /** @var BaseForm */
    private $baseFormFactory;

    /** @var SettingsFacade */
    private $settingsFacade;

    public function __construct(BaseForm $baseForm, SettingsFacade $settingsFacade)
    {
        $this->baseFormFactory = $baseForm;
        $this->settingsFacade  = $settingsFacade;
    }

    /**
     * Vytvoří formulář.
     *
     * @throws SettingsException
     * @throws \Throwable
     */
    public function create() : Form
    {
        $form = $this->baseFormFactory->create();

        $renderer                                   = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-sm-5 col-xs-5 control-label"';

        $ticketsAllowedCheckbox = $form->addCheckbox('ticketsAllowed', 'admin.configuration.payment.tickets.allowed');
        $ticketsAllowedCheckbox->addCondition($form::EQUAL, true)
                ->toggle('tickets-from');

        $form->addDateTimePicker('ticketsFrom', 'admin.configuration.payment.tickets.from')
                ->setOption('id', 'tickets-from');

        $form->addSubmit('submit', 'admin.common.save');

        $ticketsFrom = $this->settingsFacade->getDateTimeValue(Settings::TICKETS_FROM);

        $form->setDefaults(
            [
                    'ticketsAllowed' => $ticketsFrom !== null,
                    'ticketsFrom' => $ticketsFrom,
                ]
        );

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws SettingsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Throwable
     */
    public function processForm(Form $form, \stdClass $values) : void
    {
        if ($values['ticketsAllowed']) {
            $this->settingsFacade->setDateTimeValue(Settings::TICKETS_FROM, $values['ticketsFrom']);
        } else {
            $this->settingsFacade->setDateTimeValue(Settings::TICKETS_FROM, null);
        }
    }
}
