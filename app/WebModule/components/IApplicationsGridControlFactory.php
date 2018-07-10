<?php
declare(strict_types=1);

namespace App\WebModule\Components;


/**
 * Factory komponenty pro správu vlastních přihlášek.
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
