<?php

namespace App\WebModule\Presenters;

use App\Presenters\BasePresenter;

abstract class InstallBasePresenter extends BasePresenter
{
    /**
     * @return CssLoader
     */
    protected function createComponentCss()
    {
        return $this->webLoader->createCssLoader('web');
    }

    /**
     * @return JavaScriptLoader
     */
    protected function createComponentJs()
    {
        return $this->webLoader->createJavaScriptLoader('web');
    }

    public function startup()
    {
        parent::startup();

        if (!$this->context->parameters['installed']['connection'] ||
            !$this->context->parameters['installed']['schema'] ||
            !$this->context->parameters['installed']['skautIS'] ||
            !$this->context->parameters['installed']['admin']
        ) {
            $this->redirect(':Install:Install:default');
        }
    }
}