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

    protected function form() {
        $form = new \Nette\Application\UI\Form;

        //$form->setTranslator($this->getTranslator());

        $form->setRenderer(new \Nette\Forms\Rendering\BootstrapFormRenderer);

        $form->getElementPrototype()
            ->class('form-horizontal page-form')
            ->role('form');

        return $form;
    }
}