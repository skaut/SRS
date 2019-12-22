<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use Nette;
use Nette\Application\UI\Form;
use Nette\Localization\ITranslator;
use Nextras\FormsRendering\Renderers\Bs3FormRenderer;

/**
 * BaseFormFactory pro WebModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BaseFormFactory
{
    use Nette\SmartObject;

    /** @var ITranslator */
    private $translator;

    public function __construct(ITranslator $translator)
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
