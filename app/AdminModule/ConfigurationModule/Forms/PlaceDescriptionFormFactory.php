<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use App\Services\QueryBus;
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

    private BaseFormFactory $baseFormFactory;

    private CommandBus $commandBus;

    private QueryBus $queryBus;

    public function __construct(BaseFormFactory $baseForm, CommandBus $commandBus, QueryBus $queryBus)
    {
        $this->baseFormFactory = $baseForm;
        $this->commandBus      = $commandBus;
        $this->queryBus        = $queryBus;
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

        $form->addTextArea('placeDescription', 'admin.configuration.place_description')
            ->setHtmlAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'placeDescription' => $this->queryBus->handle(new SettingStringValueQuery(Settings::PLACE_DESCRIPTION)),
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
    public function processForm(Form $form, stdClass $values): void
    {
        $this->commandBus->handle(new SetSettingStringValue(Settings::PLACE_DESCRIPTION, $values->placeDescription));
    }
}
