<?php

declare(strict_types=1);

namespace App\InstallModule\Presenters;

use App\Presenters\BasePresenter;
use Nette\Localization\ITranslator;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\JavaScriptLoader;

/**
 * BasePresenter pro InstallModule.
 */
abstract class InstallBasePresenter extends BasePresenter
{
    /** @inject */
    public ITranslator $translator;

    /**
     * Načte css podle konfigurace v common.neon.
     */
    protected function createComponentCss(): CssLoader
    {
        return $this->webLoader->createCssLoader('install');
    }

    /**
     * Načte javascript podle konfigurace v common.neon.
     */
    protected function createComponentJs(): JavaScriptLoader
    {
        return $this->webLoader->createJavaScriptLoader('install');
    }
}
