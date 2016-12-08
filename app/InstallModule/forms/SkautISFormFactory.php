<?php

namespace App\InstallModule\Forms;

use Nette\Application\UI\Form;

class SkautISFormFactory
{
    /**
     * @var \App\InstallModule\Forms\BaseFormFactory
     * @inject
     */
    public $baseFormFactory; //TODO

    public function create()
    {
        $form = $this->baseFormFactory->create();

        $skautisVersions = [
            true => 'testovací',
            false => 'ostrá'
        ];

        $form->addRadioList('skautis_version', 'SkautIS verze:', $skautisVersions)
            ->getSeparatorPrototype()->setName(NULL)
            ->setDefaultValue(false);

        $form->addText('skautis_app_id', 'SkautIS app ID:')
            ->addRule(Form::FILLED, 'Zadejte skautIS App ID');

        $form->addSubmit('submit', 'Pokračovat');

        return $form;
    }
}