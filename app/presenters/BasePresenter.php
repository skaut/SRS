<?php

namespace App\Presenters;

use Nette;


abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    /**
     * @var \WebLoader\Nette\LoaderFactory
     * @inject
     */
    public $webLoader;

    /**
     * @var \Kdyby\Translation\Translator
     * @inject
     */
    public $translator;


    public function flashMessage($message, $type = 'info', $icon = null, $count = null, $parameters = [])
    {
        if ($icon)
            return parent::flashMessage('<span class="fa fa-' . $icon . '"></span> ' .
                $this->translator->translate($message, $count, $parameters), $type);
        else
            return parent::flashMessage($this->translator->translate($message, $count, $parameters), $type);
    }
}