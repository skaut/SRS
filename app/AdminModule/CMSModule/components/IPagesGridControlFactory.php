<?php

namespace App\AdminModule\CMSModule\Components;


/**
 * Factory komponenty pro správu stránek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IPagesGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return PagesGridControl
     */
    function create();
}
