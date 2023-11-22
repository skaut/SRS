<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use Nette;
use Nette\Application\UI\Form;
use Nette\Localization\Translator;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;

/**
 * BaseFormFactory pro WebModule.
 */
class BaseFormFactory
{
    use Nette\SmartObject;

    public function __construct(private Translator $translator)
    {
    }

    /**
     * Vytvoří formulář.
     */
    public function create(): Form
    {
        $form = new Form();
        $form->setTranslator($this->translator);

        $renderer                                   = new Bs4FormRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-md-9"';
        $renderer->wrappers['label']['container']   = 'div class="col-md-3 col-form-label"';

        $form->setRenderer($renderer);

        return $form;
    }
}
