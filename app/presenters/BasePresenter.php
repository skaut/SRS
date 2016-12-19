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

    protected function checkInstallationStatus()
    {
        if ($this->checkInstallationError())
            $this->redirect(':Install:Install:error');

        return ($this->context->parameters['installed']['connection'] &&
            $this->context->parameters['installed']['schema'] &&
            $this->context->parameters['installed']['skautIS'] &&
            $this->context->parameters['installed']['admin']
        );
    }

    protected function checkInstallationError() {
        return ((!$this->context->parameters['installed']['connection'] && (
                    $this->context->parameters['installed']['schema'] ||
                    $this->context->parameters['installed']['skautIS'] ||
                    $this->context->parameters['installed']['admin']))
            ||
            (!$this->context->parameters['installed']['schema'] && (
                    $this->context->parameters['installed']['skautIS'] ||
                    $this->context->parameters['installed']['admin']))
            ||
            (!$this->context->parameters['installed']['skautIS'] &&
                $this->context->parameters['installed']['admin'])
        );
    }
}