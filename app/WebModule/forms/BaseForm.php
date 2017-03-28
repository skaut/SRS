<?php

namespace App\WebModule\Forms;

use Kdyby\Translation\Translator;
use Nette;
use Nette\Application\UI\Form;
use Nextras\Forms\Rendering\Bs3FormRenderer;


/**
 * BaseForm pro WebModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BaseForm extends Nette\Object
{
    /** @var Translator */
    private $translator;


    /**
     * BaseForm constructor.
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Vytvoří formulář.
     * @return Form
     */
    public function create()
    {
        $form = new Form;
        $form->setTranslator($this->translator);

        $renderer = new Bs3FormRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-9 col-xs-9"';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-3 col-xs-3 control-label"';

        $form->setRenderer($renderer);

        return $form;
    }
}