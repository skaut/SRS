<?php

namespace App\InstallModule\Presenters;


use App\Presenters\BasePresenter;

abstract class InstallBasePresenter extends BasePresenter
{
    /**
     * @var \Kdyby\Translation\Translator
     * @inject
     */
    public $translator;

    /**
     * @return CssLoader
     */
    protected function createComponentCss()
    {
        return $this->webLoader->createCssLoader('install');
    }

    /**
     * @return JavaScriptLoader
     */
    protected function createComponentJs()
    {
        return $this->webLoader->createJavaScriptLoader('install');
    }
}