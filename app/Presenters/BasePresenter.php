<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Localization\Translator;
use stdClass;
use WebLoader\Nette\LoaderFactory;

/**
 * BasePresenter.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    /** @inject */
    public LoaderFactory $webLoader;

    /** @inject */
    public Translator $translator;

    /**
     * Zobrazí přeloženou zprávu.
     *
     * @param string   $message
     * @param string[] $parameters
     */
    public function flashMessage($message, string $type = 'info', ?string $icon = null, ?int $count = null, array $parameters = []): stdClass
    {
        if ($icon) {
            return parent::flashMessage('<span class="fa fa-' . $icon . '"></span> ' .
                $this->translator->translate($message, $count, $parameters), $type);
        }

        return parent::flashMessage($this->translator->translate($message, $count, $parameters), $type);
    }
}
