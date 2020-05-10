<?php

declare(strict_types=1);

namespace App\Model\User\Application;

use App\Model\Acl\Role;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita přihláška rolí.
 *
 * @ORM\Entity(repositoryClass="RolesApplicationRepository")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class RolesApplication extends Application
{
    /** @var string */
    protected $type = Application::ROLES;

    /**
     * @param Collection|Role[] $roles
     */
    public function setRoles(Collection $roles) : void
    {
        $this->roles->clear();
        foreach ($roles as $role) {
            $this->roles->add($role);
        }
    }
}
