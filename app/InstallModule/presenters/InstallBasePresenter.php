<?php

namespace App\InstallModule\Presenters;

use App\Presenters\BasePresenter;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\JavaScriptLoader;


/**
 * BasePresenter pro InstallModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class InstallBasePresenter extends BasePresenter
{
    /**
     * @var \Kdyby\Translation\Translator
     * @inject
     */
    public $translator;


    /**
     * Načte css podle konfigurace v config.neon.
     * @return CssLoader
     */
    protected function createComponentCss()
    {
        return $this->webLoader->createCssLoader('install');
    }

    /**
     * Načte javascript podle konfigurace v config.neon.
     * @return JavaScriptLoader
     */
    protected function createComponentJs()
    {
        return $this->webLoader->createJavaScriptLoader('install');
    }
}