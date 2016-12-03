<?php

namespace App\InstallModule\Forms;

use Nette\Application\UI\Form;

class DatabaseFormFactory
{
    public function create()
    {
        $form = new Form;

        $form->addText('host', 'Host:')
            ->addRule(Form::FILLED, 'Zadejte Host')->setDefaultValue('localhost');

        $form->addText('dbname', 'Databáze:')
            ->addRule(Form::FILLED, 'Zadejte Databázi');

        $form->addText('user', 'Uživatel:')
            ->addRule(Form::FILLED, 'Zadejte Uživatele');

        $form->addPassword('password', 'Heslo:')
            ->addRule(Form::FILLED, 'Zadejte Heslo:');

        $form->addSubmit('submit', 'Pokračovat')->getControlPrototype()->class('btn');

        return $form;
    }
}