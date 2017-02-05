<?php

namespace App\AdminModule\CMSModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\ACL\Role;
use Nette\Application\UI\Form;

class FaqFormFactory
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

        $form->addHidden('id');

        $form->addTextArea('question', 'admin.cms.faq_question')
            ->addRule(Form::FILLED, 'admin.cms.faq_question_empty')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addTextArea('answer', 'admin.cms.faq_answer')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addCheckbox('public', 'admin.cms.faq_status_form');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');

        return $form;
    }
}
