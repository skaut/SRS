<?php
declare(strict_types=1);

namespace App\AdminModule\Components;


/**
 * Factory komponenty pro správu přihlášek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IApplicationsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return ApplicationsGridControl
     */
    public function create();
}
