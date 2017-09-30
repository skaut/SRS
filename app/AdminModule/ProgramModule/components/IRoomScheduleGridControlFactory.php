<?php

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
     * @return RoomScheduleGridControl
     */
    public function create();
}
