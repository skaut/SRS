<?php

namespace App\AdminModule\Components;


/**
 * Rozhraní komponenty pro správu uživatelů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IUsersGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return UsersGridControl
     */
    function create();
}