<?php

declare(strict_types=1);

namespace App\Model\User;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

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
    public function setRoles(Collection $roles) : void
    {
        $this->roles = $roles;
    }
}
