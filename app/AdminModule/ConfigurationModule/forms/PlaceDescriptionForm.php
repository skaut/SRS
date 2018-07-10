<?php
declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro nastavení popisu cesty.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PlaceDescriptionForm
{
    use Nette\SmartObject;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var  SettingsRepository */
    private $settingsRepository;


    /**
     * PlaceDescriptionForm constructor.
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
     * @throws \Throwable
     */
    public function create()
    {
        $form = $this->baseFormFactory->create();

        $form->addTextArea('placeDescription', 'admin.configuration.place_description')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'placeDescription' => $this->settingsRepository->getValue(Settings::PLACE_DESCRIPTION)
        ]);

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param array $values
     * @throws \App\Model\Settings\SettingsException
     */
    public function processForm(Form $form, array $values)
    {
        $this->settingsRepository->setValue(Settings::PLACE_DESCRIPTION, $values['placeDescription']);
    }
}
