<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Components;

/**
 * Factory komponenty pro správu častých otázek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IFaqGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create() : FaqGridControl;
}
