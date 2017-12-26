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
     * Role.
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role")
     * @var Collection
     */
    protected $roles;


    /**
     * RolesApplication constructor.
     */
    public function __construct()
    {
        $this->subevents = new ArrayCollection();
    }

    /**
     * @return Collection
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * @param Collection $roles
     */
    public function setRoles(Collection $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * Vrací názvy rolí oddělené čárkou.
     * @return string
     */
    public function getRolesText() : string
    {
        return implode(', ', $this->roles->map(function (Role $role) {return $role->getName();})->toArray());
    }

    public function getSubeventsText(): ?string
    {
        return NULL;
    }
}
