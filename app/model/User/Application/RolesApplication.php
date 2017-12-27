<?php

namespace App\Model\User;

use App\Model\ACL\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita přihláška rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="RolesApplicationRepository")
 */
class RolesApplication extends Application
{
    protected $type = Application::ROLES;


    /**
     * @param Collection $roles
     */
    public function setRoles(Collection $roles): void
    {
        $this->roles = $roles;
    }
}
