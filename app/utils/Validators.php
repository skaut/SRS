<?php
declare(strict_types=1);

namespace App\Utils;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Structure\Subevent;
use App\Model\Structure\SubeventRepository;
use App\Model\User\Application;
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
     * @param RoleRepository $roleRepository
     * @param SubeventRepository $subeventRepository
     */
    public function __construct(RoleRepository $roleRepository, SubeventRepository $subeventRepository)
    {
        $this->roleRepository = $roleRepository;
        $this->subeventRepository = $subeventRepository;
    }

    /**
     * Ověří, že není vybrána role "Neregistrovaný".
     * @param Collection|Role[] $selectedRoles
     * @param User $user
     * @return bool
     */
    public function validateRolesNonregistered(Collection $selectedRoles, User $user): bool
    {
        $nonregisteredRole = $this->roleRepository->findBySystemName(Role::NONREGISTERED);

        if ($selectedRoles->contains($nonregisteredRole)) {
            if ($user->isInRole($nonregisteredRole) && $selectedRoles->count() == 1) {
                return true;
            }
            else {
                return false;
            }
        }

        return TRUE;
    }

    /**
     * Ověří kapacitu rolí.
     * @param Collection|Role[] $selectedRoles
     * @param User $user
     * @return bool
     */
    public function validateRolesCapacities(Collection $selectedRoles, User $user): bool
    {
        foreach ($selectedRoles as $role) {
            if ($role->hasLimitedCapacity() && !$user->isInRole($role)
                && $role->countUnoccupied() < 1)
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
     * Ověří kapacitu podakcí.
     * @param Collection|Subevent[] $selectedSubevents
     * @param User $user
     * @return bool
     */
    public function validateSubeventsCapacities(Collection $selectedSubevents, User $user): bool
    {
        foreach ($selectedSubevents as $subevent) {
            if ($subevent->hasLimitedCapacity() && !$user->hasSubevent($subevent)
                && $subevent->countUnoccupied() < 1)
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

    /**
     * Ověří, zda uživatel podakci již nemá.
     * @param Collection|Subevent[] $selectedSubevents
     * @param User $user
     * @param Application|null $editedApplication
     * @return bool
     */
    public function validateSubeventsRegistered(Collection $selectedSubevents, User $user,
                                                Application $editedApplication = NULL): bool
    {
        foreach ($selectedSubevents as $subevent) {
            foreach ($user->getNotCanceledSubeventsApplications() as $application) {
                if ($application !== $editedApplication && $application->getSubevents()->contains($subevent))
                    return FALSE;
            }
        }
        return TRUE;
    }
}
