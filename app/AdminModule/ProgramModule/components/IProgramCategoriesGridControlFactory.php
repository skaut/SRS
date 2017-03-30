<?php

namespace App\AdminModule\ProgramModule\Components;


/**
 * Factory komponenty pro správu kategorií.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IProgramCategoriesGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return ProgramCategoriesGridControl
     */
    function create();
}
