<?php

namespace App\WebModule\Components;


/**
 * Factory komponenty s přehledem uživatelů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IUsersContentControlFactory
{
    /**
     * @return UsersContentControl
     */
    public function create();
}
