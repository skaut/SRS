<?php

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
     * @return ProgramBlocksGridControl
     */
    function create();
}