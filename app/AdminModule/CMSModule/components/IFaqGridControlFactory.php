<?php

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
    function create();
}