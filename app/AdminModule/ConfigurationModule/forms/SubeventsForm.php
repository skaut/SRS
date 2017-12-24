<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro nastavení podakcí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SubeventsForm extends Nette\Object
{
    /** @var BaseForm */
    private $baseFormFactory;

    /** @var  SettingsRepository */
    private $settingsRepository;


    /**
     * ApplicationForm constructor.
     * @param BaseForm $baseForm
     * @param SettingsRepository $settingsRepository
     */
    public function __construct(BaseForm $baseForm, SettingsRepository $settingsRepository)
    {
        $this->baseFormFactory = $baseForm;
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * Vytvoří formulář.
     * @return Form
     * @throws \App\Model\Settings\SettingsException
     */
    public function create()
    {
        $form = $this->baseFormFactory->create();

        $form->addCheckbox('isAllowedAddSubeventsAfterPayment', 'admin.configuration.is_allowed_add_subevents_after_payment');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'isAllowedAddSubeventsAfterPayment' => $this->settingsRepository->getValue(Settings::IS_ALLOWED_ADD_SUBEVENTS_AFTER_PAYMENT)
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     * @throws \App\Model\Settings\SettingsException
     */
    public function processForm(Form $form, \stdClass $values)
    {
        $this->settingsRepository->setValue(Settings::IS_ALLOWED_ADD_SUBEVENTS_AFTER_PAYMENT, $values['isAllowedAddSubeventsAfterPayment']);
    }
}
