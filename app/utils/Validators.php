<?php

declare(strict_types=1);

namespace App\Utils;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\ProgramRepository;
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

    /** @var ProgramRepository */
    private $programRepository;

    public function __construct(RoleRepository $roleRepository, SubeventRepository $subeventRepository, ProgramRepository $programRepository)
    {
        $this->roleRepository     = $roleRepository;
        $this->subeventRepository = $subeventRepository;
        $this->programRepository  = $programRepository;
    }

    /**
     * Ověří, že není vybrána role "Neregistrovaný".
     * @param Collection|Role[] $selectedRoles
     */
    public function validateRolesNonregistered(Collection $selectedRoles, User $user) : bool
    {
        $nonregisteredRole = $this->roleRepository->findBySystemName(Role::NONREGISTERED);

        if ($selectedRoles->contains($nonregisteredRole)) {
            if ($user->isInRole($nonregisteredRole) && $selectedRoles->count() === 1) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Ověří kapacitu rolí.
     * @param Collection|Role[] $selectedRoles
     */
    public function validateRolesCapacities(Collection $selectedRoles, User $user) : bool
    {
        foreach ($selectedRoles as $role) {
            if ($role->hasLimitedCapacity() && ! $user->isInRole($role) && $role->countUnoccupied() < 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ověří kompatibilitu rolí.
     * @param Collection|Role[] $selectedRoles
     */
    public function validateRolesIncompatible(Collection $selectedRoles, Role $testRole) : bool
    {
        if (! $selectedRoles->contains($testRole)) {
            return true;
        }

        foreach ($testRole->getIncompatibleRoles() as $incompatibleRole) {
            if ($selectedRoles->contains($incompatibleRole)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ověří výběr vyžadovaných rolí.
     * @param Collection|Role[] $selectedRoles
     */
    public function validateRolesRequired(Collection $selectedRoles, Role $testRole) : bool
    {
        if (! $selectedRoles->contains($testRole)) {
            return true;
        }

        foreach ($testRole->getRequiredRolesTransitive() as $requiredRole) {
            if (! $selectedRoles->contains($requiredRole)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ověří registrovatelnost rolí.
     * @param Collection|Role[] $selectedRoles
     */
    public function validateRolesRegisterable(Collection $selectedRoles, User $user) : bool
    {
        foreach ($selectedRoles as $role) {
            if (! $role->isRegisterableNow() && ! $user->isInRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ověří kapacitu podakcí.
     * @param Collection|Subevent[] $selectedSubevents
     */
    public function validateSubeventsCapacities(Collection $selectedSubevents, User $user) : bool
    {
        foreach ($selectedSubevents as $subevent) {
            if ($subevent->hasLimitedCapacity() && ! $user->hasSubevent($subevent) && $subevent->countUnoccupied() < 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ověří kompatibilitu podakcí.
     * @param Collection|Subevent[] $selectedSubevents
     */
    public function validateSubeventsIncompatible(Collection $selectedSubevents, Subevent $testSubevent) : bool
    {
        if (! $selectedSubevents->contains($testSubevent)) {
            return true;
        }

        foreach ($testSubevent->getIncompatibleSubevents() as $incompatibleSubevent) {
            if ($selectedSubevents->contains($incompatibleSubevent)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ověří výběr vyžadovaných podakcí.
     * @param Collection|Subevent[] $selectedSubevents
     */
    public function validateSubeventsRequired(Collection $selectedSubevents, Subevent $testSubevent) : bool
    {
        if (! $selectedSubevents->contains($testSubevent)) {
            return true;
        }

        foreach ($testSubevent->getRequiredSubeventsTransitive() as $requiredSubevent) {
            if (! $selectedSubevents->contains($requiredSubevent)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ověří, zda uživatel podakci již nemá.
     * @param Collection|Subevent[] $selectedSubevents
     */
    public function validateSubeventsRegistered(
        Collection $selectedSubevents,
        User $user,
        ?Application $editedApplication = null
    ) : bool {
        foreach ($selectedSubevents as $subevent) {
            foreach ($user->getNotCanceledSubeventsApplications() as $application) {
                if ($application !== $editedApplication && $application->getSubevents()->contains($subevent)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Ověří, zda může být program automaticky přihlašovaný.
     */
    public function validateBlockAutoRegistered(Block $block) : bool
    {
        if ($block->getMandatory() !== ProgramMandatoryType::AUTO_REGISTERED
            && ($block->getProgramsCount() > 1 || (
                $block->getProgramsCount() === 1
                && $this->programRepository->hasOverlappingProgram(
                    $block->getPrograms()->first()->getId(),
                    $block->getPrograms()->first()->getStart(),
                    $block->getPrograms()->first()->getEnd()
                )
                )
            )
        ) {
            return false;
        }
        return true;
    }
}
