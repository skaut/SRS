<?php

namespace App\Install\Forms;


class SkautISForm
{
    public function create()
    {
        $form = new Form;

        $form->addText('skautis_app_id', 'SkautIS app ID:')
            ->addRule(Form::FILLED, 'Zadejte skautIS App ID');

        $form->addSubmit('submit', 'Pokračovat')->getControlPrototype()->class('btn');

        return $form;
    }
}