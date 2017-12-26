<?php

namespace App\Utils;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Structure\Subevent;
use App\Model\Structure\SubeventRepository;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;


/**
 * Třída s vlastními validátory.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class Validators
{
    /** @var RoleRepository */
    private $roleRepository;

    /** @var SubeventRepository */
    private $subeventRepository;


    /**
     * Validators constructor.
     */
    public function __construct(RoleRepository $roleRepository, SubeventRepository $subeventRepository)
    {
        $this->roleRepository = $roleRepository;
        $this->subeventRepository = $subeventRepository;
    }

    /**
     * Ověří kapacitu rolí.
     * @param Collection|Role[] $selectedRoles
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function validateRolesCapacities(Collection $selectedRoles, User $user): bool
    {
        foreach ($selectedRoles as $role) {
            if ($role->hasLimitedCapacity() && !$user->isInRole($role)
                && $this->roleRepository->countUnoccupiedInRole($role) < 1)
                    return FALSE;
        }

        return TRUE;
    }

    /**
     * Ověří kompatibilitu rolí.
     * @param Collection|Role[] $selectedRoles
     * @param Role $testRole
     * @return bool
     */
    public function validateRolesIncompatible(Collection $selectedRoles, Role $testRole): bool
    {
        if (!$selectedRoles->contains($testRole))
            return TRUE;

        foreach ($testRole->getIncompatibleRoles() as $incompatibleRole) {
            if ($selectedRoles->contains($incompatibleRole))
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Ověří výběr vyžadovaných rolí.
     * @param Collection|Role[] $selectedRoles
     * @param Role $testRole
     * @return bool
     */
    public function validateRolesRequired(Collection $selectedRoles, Role $testRole): bool
    {
        if (!$selectedRoles->contains($testRole))
            return TRUE;

        foreach ($testRole->getRequiredRolesTransitive() as $requiredRole) {
            if (!$selectedRoles->contains($requiredRole))
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Ověří registrovatelnost rolí.
     * @param Collection|Role[] $selectedRoles
     * @param User $user
     * @return bool
     */
    public function validateRolesRegisterable(Collection $selectedRoles, User $user): bool
    {
        foreach ($selectedRoles as $role) {
            if (!$role->isRegisterableNow() && !$user->isInRole($role))
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Ověří, že je vybrána alespoň jedna podakce.
     * @param $selectedSubevents
     * @return bool
     */
    public function validateSubeventsEmpty(Collection $selectedSubevents): bool
    {
        if ($selectedSubevents->isEmpty())
            return FALSE;
        return TRUE;
    }

    /**
     * Ověří kapacitu podakcí.
     * @param Collection|Subevent[] $selectedSubevents
     * @param User $user
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function validateSubeventsCapacities(Collection $selectedSubevents, User $user): bool
    {
        foreach ($selectedSubevents as $subevent) {
            if ($subevent->hasLimitedCapacity() && !$user->hasSubevent($subevent)
                && $this->subeventRepository->countUnoccupiedInSubevent($subevent) < 1)
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Ověří kompatibilitu podakcí.
     * @param Collection|Subevent[] $selectedSubevents
     * @param Subevent $testSubevent
     * @return bool
     */
    public function validateSubeventsIncompatible(Collection $selectedSubevents, Subevent $testSubevent): bool
    {
        if (!$selectedSubevents->contains($testSubevent))
            return TRUE;

        foreach ($testSubevent->getIncompatibleSubevents() as $incompatibleSubevent) {
            if ($selectedSubevents->contains($incompatibleSubevent))
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Ověří výběr vyžadovaných podakcí.
     * @param Collection|Subevent[] $selectedSubevents
     * @param Subevent $testSubevent
     * @return bool
     */
    public function validateSubeventsRequired(Collection $selectedSubevents, Subevent $testSubevent): bool
    {
        if (!$selectedSubevents->contains($testSubevent))
            return TRUE;

        foreach ($testSubevent->getRequiredSubeventsTransitive() as $requiredSubevent) {
            if (!$selectedSubevents->contains($requiredSubevent))
                return FALSE;
        }

        return TRUE;
    }
}