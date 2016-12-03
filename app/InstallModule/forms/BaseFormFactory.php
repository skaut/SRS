<?php

namespace App\InstallModule\Forms;

use Nette\Application\UI\Form;

class BaseFormFactory
{
    /**
     * @var \Kdyby\Translation\Translator
     * @inject
     */
    public $translator;

    /**
     * @var \Nextras\Forms\Rendering\BaseFormFactory
     * @inject
     */
    public $renderer;

    public function create()
    {
        $form = new Form;
        $form->setTranslator($this->translator);
        $form->setRenderer($this->renderer);
        return $form;
    }
}