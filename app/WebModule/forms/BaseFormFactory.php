<?php

namespace App\WebModule\Forms;

use Nette\Application\UI\Form;

class BaseFormFactory
{
    /**
     * @var \Kdyby\Translation\Translator
     */
    private $translator;

    public function __construct(\Kdyby\Translation\Translator $translator)
    {
        $this->translator = $translator;
    }

    public function create()
    {
        $form = new Form;
        $form->setTranslator($this->translator);
        $form->setRenderer(new \Nextras\Forms\Rendering\Bs3FormRenderer());
        return $form;
    }
}