<?php

declare(strict_types=1);

namespace App\Model\Application;

use App\Model\Acl\Role;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita přihláška rolí
 */
#[ORM\Entity]
class RolesApplication extends Application
{
    protected string $type = Application::ROLES;

    /**
     * @param Collection<int, Role> $roles
     */
    public function setRoles(Collection $roles): void
    {
        $this->roles->clear();
        foreach ($roles as $role) {
            $this->addRole($role);
        }
    }

    public function addRole(Role $role): void
    {
        if (! $this->roles->contains($role)) {
            $this->roles->add($role);
        }
    }
}
