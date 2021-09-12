<?php

declare(strict_types=1);

namespace App\ExportModule\Presenters;

use App\Presenters\BasePresenter;
use WebLoader\Nette\CssLoader;

/**
 * BasePresenter pro ExportModule.
 */
abstract class ExportBasePresenter extends BasePresenter
{
    /**
     * Načte css podle konfigurace v common.neon.
     */
    protected function createComponentCss(): CssLoader
    {
        return $this->webLoader->createCssLoader('export');
    }
}
