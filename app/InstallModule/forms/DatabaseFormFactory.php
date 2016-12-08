<?php

namespace App\InstallModule\Forms;

use Nette\Application\UI\Form;

class DatabaseFormFactory
{
    private $baseFormFactory;

    public function __construct(BaseFormFactory $baseFormFactory)
    {
        $this->baseFormFactory = $baseFormFactory;
    }

    public function create()
    {
        $form = $this->baseFormFactory->create();

        $form->addText('host', $form->getTranslator()->translate('install.databaseForm.host'))
            ->addRule(Form::FILLED, $form->getTranslator()->translate('install.databaseForm.emptyHost'))
            ->setDefaultValue('localhost');

        $form->addText('dbname', $form->getTranslator()->translate('install.databaseForm.dbname'))
            ->addRule(Form::FILLED, $form->getTranslator()->translate('install.databaseForm.emptyDbname'));

        $form->addText('user', $form->getTranslator()->translate('install.databaseForm.user'))
            ->addRule(Form::FILLED, $form->getTranslator()->translate('install.databaseForm.emptyUser'));

        $form->addPassword('password', $form->getTranslator()->translate('install.databaseForm.password'))
            ->addRule(Form::FILLED, $form->getTranslator()->translate('install.databaseForm.emptyPassword'));

        $form->addSubmit('submit', $form->getTranslator()->translate('install.databaseForm.continue'));
        $form->onValidate[] = array($this, 'formSucceeded');

        return $form;
    }

    public function formSucceeded(Form $form, $values)
    {

    }

}