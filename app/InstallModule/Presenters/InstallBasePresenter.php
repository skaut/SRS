<?php

declare(strict_types=1);

namespace App\InstallModule\Presenters;

use App\Presenters\BasePresenter;
use Nette\DI\Attributes\Inject;
use Nette\Localization\Translator;

/**
 * BasePresenter pro InstallModule
 */
abstract class InstallBasePresenter extends BasePresenter
{
    #[Inject]
    public Translator $translator;
}
