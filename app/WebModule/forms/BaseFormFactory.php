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

        $renderer = new \Nextras\Forms\Rendering\Bs3FormRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-9 col-xs-9"';
		$renderer->wrappers['label']['container'] = 'div class="col-sm-3 col-xs-3 control-label"';

        $form->setRenderer($renderer);

        return $form;
    }
}