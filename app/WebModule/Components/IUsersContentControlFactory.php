<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s přehledem uživatelů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IUsersContentControlFactory
{
    public function create() : UsersContentControl;
}
