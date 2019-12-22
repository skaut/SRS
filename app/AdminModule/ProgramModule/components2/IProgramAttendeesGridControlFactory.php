<?php

declare(strict_types=1);

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
     */
    public function create() : ProgramAttendeesGridControl;
}
