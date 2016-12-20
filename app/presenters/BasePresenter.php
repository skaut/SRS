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
}