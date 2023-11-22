<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty pro správu vlastních přihlášek.
 */
interface IApplicationsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): ApplicationsGridControl;
}
