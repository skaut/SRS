<?php

declare(strict_types=1);

namespace App\Presenters;

use Kdyby\Translation\Translator;
use Nette;
use WebLoader\Nette\LoaderFactory;

/**
 * BasePresenter.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    /**
     * @var LoaderFactory
     * @inject
     */
    public $webLoader;

    /**
     * @var Translator
     * @inject
     */
    public $translator;


    /**
     * Zobrazí přeloženou zprávu.
     * @param string[] $parameters
     */
    public function flashMessage($message, $type = 'info', ?string $icon = null, ?int $count = null, array $parameters = []) : \stdClass
    {
        if ($icon) {
            return parent::flashMessage('<span class="fa fa-' . $icon . '"></span> ' .
                $this->translator->translate($message, $count, $parameters), $type);
        }

        return parent::flashMessage($this->translator->translate($message, $count, $parameters), $type);
    }
}
