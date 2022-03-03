<?php

declare(strict_types=1);

namespace App\InstallModule\Presenters;

use App\Presenters\BasePresenter;
use Nette\Localization\Translator;

/**
 * BasePresenter pro InstallModule.
 */
abstract class InstallBasePresenter extends BasePresenter
{
    /** @inject */
    public Translator $translator;
}
