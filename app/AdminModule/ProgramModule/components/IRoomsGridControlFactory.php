<?php
declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Components;


/**
 * Factory komponenty pro správu místností.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IRoomsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return RoomsGridControl
     */
    public function create();
}
