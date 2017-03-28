<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro nastavení přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationForm extends Nette\Object
{
    /** @var BaseForm */
    private $baseForm;

    /** @var  SettingsRepository */
    private $settingsRepository;


    /**
     * ApplicationForm constructor.
     * @param BaseForm $baseForm
     * @param SettingsRepository $settingsRepository
     */
    public function __construct(BaseForm $baseForm, SettingsRepository $settingsRepository)
    {
        $this->baseForm = $baseForm;
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * Vytvoří formulář.
     * @return Form
     */
    public function create()
    {
        $form = $this->baseForm->create();

        $form->addTextArea('applicationAgreement', 'admin.configuration.application_agreement')
            ->setAttribute('rows', 5);

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'applicationAgreement' => $this->settingsRepository->getValue(Settings::APPLICATION_AGREEMENT)
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     */
    public function processForm(Form $form, \stdClass $values)
    {
        $this->settingsRepository->setValue(Settings::APPLICATION_AGREEMENT, $values['applicationAgreement']);
    }
}
