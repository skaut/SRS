<?php

namespace App\InstallModule\Forms;

use Nette\Application\UI\Form;

class SkautISFormFactory
{
    private $baseFormFactory;

    public function __construct(BaseFormFactory $baseFormFactory, \Kdyby\Doctrine\EntityManager $em)
    {
        $this->baseFormFactory = $baseFormFactory;
    }

    public function create()
    {
        $form = $this->baseFormFactory->create();

        $skautisVersions = [
            'false' => $form->getTranslator()->translate('install.skautis.version_production'),
            'true' => $form->getTranslator()->translate('install.skautis.version_development')
        ];

        $form->addRadioList('skautis_version', $form->getTranslator()->translate('install.skautis.skautis_version'), $skautisVersions)
            ->setDefaultValue('false')
            ->getSeparatorPrototype()->setName(NULL);

        $form->addText('skautis_app_id', $form->getTranslator()->translate('install.skautis.skautis_appid'))
            ->addRule(Form::FILLED, $form->getTranslator()->translate('install.skautis.empty_skautis_appid'))
            ->addRule(Form::PATTERN, $form->getTranslator()->translate('install.skautis.invalid_skautis_appid'), '([0-9a-fA-F]){8}(-([0-9a-fA-F]){4}){3}-([0-9a-fA-F]){12}');

        $form->addSubmit('submit', $form->getTranslator()->translate('install.skautis.continue'));

        return $form;
    }
}