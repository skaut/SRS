<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Components;

/**
 * Factory komponenty pro správu podakcí.
 */
interface ISubeventsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): SubeventsGridControl;
}
