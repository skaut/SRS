<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Components;

/**
 * Factory komponenty pro správu programových bloků.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IProgramBlocksGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create() : ProgramBlocksGridControl;
}
