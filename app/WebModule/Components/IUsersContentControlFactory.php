<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s přehledem uživatelů.
 */
interface IUsersContentControlFactory
{
    public function create(): UsersContentControl;
}
