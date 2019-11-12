<?php

declare(strict_types=1);

namespace App\InstallModule\Forms;

use Kdyby\Translation\Translator;
use Nette;
use Nette\Application\UI\Form;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * BaseForm pro AdminModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BaseForm
{
    use Nette\SmartObject;

    /** @var Translator */
    private $translator;


    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Vytvoří formulář.
     */
    public function create() : Form
    {
        $form = new Form();
        $form->setTranslator($this->translator);

        $renderer                                   = new Bs3FormRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-9 col-xs-9"';
        $renderer->wrappers['label']['container']   = 'div class="col-sm-3 col-xs-3 control-label"';

        $form->setRenderer($renderer);

        return $form;
    }
}
