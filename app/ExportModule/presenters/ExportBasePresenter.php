<?php

declare(strict_types=1);

namespace App\ExportModule\Presenters;

use App\Presenters\BasePresenter;
use WebLoader\Nette\CssLoader;

/**
 * BasePresenter pro ExportModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class ExportBasePresenter extends BasePresenter
{
    /**
     * Načte css podle konfigurace v config.neon.
     */
    protected function createComponentCss() : CssLoader
    {
        return $this->webLoader->createCssLoader('export');
    }
}
