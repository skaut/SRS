<?php

namespace App\InstallModule\Presenters;

use App\Presenters\BasePresenter;

abstract class InstallBasePresenter extends BasePresenter
{
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