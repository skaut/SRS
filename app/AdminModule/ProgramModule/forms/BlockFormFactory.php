<?php

namespace App\AdminModule\ProgramModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use Nette\Application\UI\Form;

class BlockFormFactory
{
    /**
     * @var BaseFormFactory
     */
    private $baseFormFactory;

    public function __construct(BaseFormFactory $baseFormFactory)
    {
        $this->baseFormFactory = $baseFormFactory;
    }

    public function create()
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();

        $form->addText('name', 'admin.program.blocks_name')
            ->addRule(Form::FILLED, 'admin.program.blocks_name_empty');

        $form->addSelect('category', 'admin.program.blocks_category');

        $form->addSelect('lector', 'admin.program.blocks_lector');

        $form->addSelect('room', 'admin.program.blocks_room');

        $form->addSelect('duration', 'admin.program.blocks_duration')
            ->addRule(Form::FILLED, 'admin.program.blocks_duration_empty');

        $form->addText('capacity', 'admin.program.blocks_capacity')
            ->addCondition(Form::FILLED)->addRule(Form::NUMERIC, 'admin.program.blocks_capacity_format');

        $form->addText('tools', 'admin.program.blocks_tools');

        $form->addTextArea('perex', 'admin.program.blocks_perex');

        $form->addTextArea('description', 'admin.program.blocks_description')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.common.save');

        return $form;
    }
}
