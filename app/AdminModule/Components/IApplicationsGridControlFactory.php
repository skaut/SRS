<?php

declare(strict_types=1);

namespace App\AdminModule\Components;

/**
 * Factory komponenty pro správu přihlášek
 */
interface IApplicationsGridControlFactory
{
    /**
     * Vytvoří komponentu
     */
    public function create(): ApplicationsGridControl;
}
