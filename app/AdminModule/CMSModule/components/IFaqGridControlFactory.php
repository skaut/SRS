<?php
declare(strict_types=1);

namespace App\AdminModule\CMSModule\Components;


/**
 * Factory komponenty pro správu častých otázek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IFaqGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return FaqGridControl
     */
    public function create(): FaqGridControl;
}
