<?php

namespace App\AdminModule\ProgramModule\Components;


/**
 * Factory komponenty pro správu účastníků programu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IProgramAttendeesGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return ProgramAttendeesGridControl
     */
    function create();
}
