<?php

namespace App\Presenters;

use Nette;


/**
 * BasePresenter.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
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


    /**
     * Zobrazí přeloženou zprávu.
     * @param $message
     * @param string $type
     * @param null $icon
     * @param null $count
     * @param array $parameters
     * @return \stdClass
     */
    public function flashMessage($message, $type = 'info', $icon = NULL, $count = NULL, $parameters = [])
    {
        if ($icon)
            return parent::flashMessage('<span class="fa fa-' . $icon . '"></span> ' .
                $this->translator->translate($message, $count, $parameters), $type);
        else
            return parent::flashMessage($this->translator->translate($message, $count, $parameters), $type);
    }
}
