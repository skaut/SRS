<?php

declare(strict_types=1);

namespace App\Model\Acl\Events;

use App\Model\Acl\Role;

class RoleUpdatedEvent
{
    private Role $role;

    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    public function getRole(): Role
    {
        return $this->role;
    }
}
