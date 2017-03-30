<?php

namespace App\AdminModule\CMSModule\Components;


/**
 * Factory komponenty pro správu aktualit.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface INewsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return NewsGridControl
     */
    public function create();
}
