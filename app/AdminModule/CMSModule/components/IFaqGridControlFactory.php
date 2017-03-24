<?php

namespace App\AdminModule\CMSModule\Components;


/**
 * Rozhraní komponenty pro správu častých otázek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IFaqGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return FaqGridControl
     */
    function create();
}