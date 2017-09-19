<?php

namespace App\AdminModule\ConfigurationModule\Components;


/**
 * Factory komponenty pro správu podakcí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface ISubeventsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return SubeventsGridControl
     */
    public function create();
}
