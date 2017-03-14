<?php

namespace App\AdminModule\ConfigurationModule\Forms;


use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use Kdyby\Translation\Translator;
use Nette;
use Nette\Application\UI\Form;

class PlaceDescriptionForm extends Nette\Object
{
    /** @var BaseForm */
    private $baseForm;

    /** @var  SettingsRepository */
    private $settingsRepository;

    public function __construct(BaseForm $baseForm, SettingsRepository $settingsRepository)
    {
        $this->baseForm = $baseForm;
        $this->settingsRepository = $settingsRepository;
    }

    public function create()
    {
        $form = $this->baseForm->create();

        $form->addTextArea('placeDescription', 'admin.configuration.place_description');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'placeDescription' => $this->settingsRepository->getValue(Settings::PLACE_DESCRIPTION)
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values) {
        $this->settingsRepository->setValue(Settings::PLACE_DESCRIPTION, $values['placeDescription']);
    }
}
