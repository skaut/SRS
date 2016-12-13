<?php

namespace App\InstallModule\Forms;

use Nette\Application\UI\Form;

class DatabaseFormFactory
{
    private $baseFormFactory;

    public function __construct(BaseFormFactory $baseFormFactory, \Kdyby\Doctrine\EntityManager $em)
    {
        $this->baseFormFactory = $baseFormFactory;
    }

    public function create()
    {
        $form = $this->baseFormFactory->create();

        $form->addText('host', $form->getTranslator()->translate('install.database.host'))
            ->addRule(Form::FILLED, $form->getTranslator()->translate('install.database.emptyHost'))
            ->setDefaultValue('localhost');

        $form->addText('dbname', $form->getTranslator()->translate('install.database.dbname'))
            ->addRule(Form::FILLED, $form->getTranslator()->translate('install.database.emptyDbname'));

        $form->addText('user', $form->getTranslator()->translate('install.database.user'))
            ->addRule(Form::FILLED, $form->getTranslator()->translate('install.database.emptyUser'));

        $form->addPassword('password', $form->getTranslator()->translate('install.database.password'))
            ->addRule(Form::FILLED, $form->getTranslator()->translate('install.database.emptyPassword'));

        $form->addSubmit('submit', $form->getTranslator()->translate('install.database.continue'));

        return $form;
    }
}