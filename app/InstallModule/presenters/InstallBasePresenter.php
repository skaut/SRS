<?php

declare(strict_types=1);

namespace App\InstallModule\Presenters;

use App\Presenters\BasePresenter;
use Kdyby\Translation\Translator;
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
     * @var Translator
     * @inject
     */
    public $translator;

    /**
     * Načte css podle konfigurace v config.neon.
     */
    protected function createComponentCss() : CssLoader
    {
        return $this->webLoader->createCssLoader('install');
    }

    /**
     * Načte javascript podle konfigurace v config.neon.
     */
    protected function createComponentJs() : JavaScriptLoader
    {
        return $this->webLoader->createJavaScriptLoader('install');
    }
}
