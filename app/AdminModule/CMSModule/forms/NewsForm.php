<?php

namespace App\AdminModule\CMSModule\Forms;

use App\AdminModule\Forms\BaseForm;
use Nette;
use Nette\Application\UI\Form;

class NewsForm extends Nette\Object
{
    /**
     * @var BaseForm
     */
    private $baseFormFactory;

    public function __construct(BaseForm $baseFormFactory)
    {
        $this->baseFormFactory = $baseFormFactory;
    }

    public function create()
    {
        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addDateTimePicker('published', 'admin.cms.news_published')
            ->addRule(Form::FILLED, 'admin.cms.news_published_empty');

        $form->addTextArea('text', 'admin.cms.news_text')
            ->addRule(Form::FILLED, 'admin.cms.news_text_empty')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');

        return $form;
    }
}
