<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Components;

/**
 * Factory komponenty pro zobrazení harmonogramu místnosti.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IRoomScheduleGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create() : RoomScheduleGridControl;
}
