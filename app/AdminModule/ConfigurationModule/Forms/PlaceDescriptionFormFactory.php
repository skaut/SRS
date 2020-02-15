<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Services\SettingsService;
use Nette;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Formulář pro nastavení popisu cesty.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PlaceDescriptionFormFactory
{
    use Nette\SmartObject;

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
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function create() : Form
    {
        $form = $this->baseFormFactory->create();

        $form->addTextArea('placeDescription', 'admin.configuration.place_description')
            ->setHtmlAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'placeDescription' => $this->settingsService->getValue(Settings::PLACE_DESCRIPTION),
        ]);

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        $this->settingsService->setValue(Settings::PLACE_DESCRIPTION, $values->placeDescription);
    }
}
