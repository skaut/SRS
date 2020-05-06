<?php

declare(strict_types=1);

namespace App\Utils;

use App\Model\Acl\Role;
use App\Model\Acl\RoleRepository;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Structure\Subevent;
use App\Model\User\Application;
use App\Model\User\User;
use App\Services\SettingsService;
use Doctrine\Common\Collections\Collection;
use Throwable;

/**
 * Třída s vlastními validátory.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class Validators
{
    /** @var RoleRepository */
    private $roleRepository;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var SettingsService */
    private $settingsService;

    public function __construct(
        RoleRepository $roleRepository,
        ProgramRepository $programRepository,
        SettingsService $settingsService
    ) {
        $this->roleRepository    = $roleRepository;
        $this->programRepository = $programRepository;
        $this->settingsService   = $settingsService;
    }

    /**
     * Ověří, že není vybrána role "Neregistrovaný".
     *
     * @param Collection|Role[] $selectedRoles
     */
    public function validateRolesNonregistered(Collection $selectedRoles, User $user) : bool
    {
        $nonregisteredRole = $this->roleRepository->findBySystemName(Role::NONREGISTERED);

        if ($selectedRoles->contains($nonregisteredRole)) {
            return $user->isInRole($nonregisteredRole) && $selectedRoles->count() === 1;
        }

        return true;
    }

    /**
     * Ověří kapacitu rolí.
     *
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
     *
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
     *
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
     *
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
     * Ověří požadovaný minimální věk.
     *
     * @param Collection|Role[] $selectedRoles
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function validateRolesMinimumAge(Collection $selectedRoles, User $user) : bool
    {
        $age = $this->settingsService->getDateValue(Settings::SEMINAR_FROM_DATE)->diff($user->getBirthdate())->y;

        foreach ($selectedRoles as $role) {
            if ($role->getMinimumAge() > $age) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ověří kapacitu podakcí.
     *
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
     *
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
     *
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
     *
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
        return $block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED
            || ($block->getProgramsCount() <= 1
                && ($block->getProgramsCount() !== 1
                    || ! $this->programRepository->hasOverlappingProgram(
                        $block->getPrograms()->first()->getId(),
                        $block->getPrograms()->first()->getStart(),
                        $block->getPrograms()->first()->getEnd()
                    )
                )
            );
    }
}
