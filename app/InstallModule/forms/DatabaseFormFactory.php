<?php

namespace App\InstallModule\Forms;

use Kdyby\Doctrine\Configuration;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Form;
use Nette\Utils\Neon;

class DatabaseFormFactory
{
    private $baseFormFactory;
    private $em;

    public function __construct(BaseFormFactory $baseFormFactory, \Kdyby\Doctrine\EntityManager $em)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->em = $em;
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