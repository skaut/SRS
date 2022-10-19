<?php

declare(strict_types=1);

namespace App\Utils;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Application\Application;
use App\Model\Program\Block;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingDateValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Subevent;
use App\Model\User\User;
use App\Services\QueryBus;
use Doctrine\Common\Collections\Collection;
use Throwable;

use function array_map;
use function explode;
use function sprintf;
use function trim;

/**
 * Třída s vlastními validátory.
 */
class Validators
{
    public function __construct(
        private QueryBus $queryBus,
        private RoleRepository $roleRepository,
        private ProgramRepository $programRepository
    ) {
    }

    /**
     * Ověří, že není vybrána role "Neregistrovaný".
     *
     * @param Collection<int, Role> $selectedRoles
     */
    public function validateRolesNonregistered(Collection $selectedRoles, User $user): bool
    {
        $nonregisteredRole = $this->roleRepository->findBySystemName(Role::NONREGISTERED);

        if ($selectedRoles->contains($nonregisteredRole)) {
            return $user->isInRole($nonregisteredRole) && $selectedRoles->count() === 1;
        }

        return true;
    }

    /**
     * Ověří požadovaný minimální věk.
     *
     * @param Collection<int, Role> $selectedRoles
     * @param string[]              $warnings
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function validateRolesMinimumAge(Collection $selectedRoles, User $user, array &$warnings = []): bool
    {
        $age    = $this->queryBus->handle(new SettingDateValueQuery(Settings::SEMINAR_FROM_DATE))->diff($user->getBirthdate())->y;
        $canary = true;
        foreach ($selectedRoles as $role) {
            $min = $role->getMinimumAge();
            if ($min > $age) {
                $warnings[] = sprintf($role->getMinimumAgeWarning(), $min, $age);
                $canary     = false;
            }
        }

        return $canary;
    }

    /**
     * Ověří požadovaný maximální věk.
     *
     * @param Collection<int, Role> $selectedRoles
     * @param string[]              $warnings
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function validateRolesMaximumAge(Collection $selectedRoles, User $user, array &$warnings = []): bool
    {
        $age    = $this->queryBus->handle(new SettingDateValueQuery(Settings::SEMINAR_TO_DATE))->diff($user->getBirthdate())->y;
        $canary = true;
        foreach ($selectedRoles as $role) {
            $max = $role->getMaximumAge();
            if ($max > 0 && $max < $age) { // Hodnota 0 je bez omezení
                $warnings[] = sprintf($role->getMaximumAgeWarning(), $max, $age);
                $canary     = false;
            }
        }

        return $canary;
    }

    /**
     * Ověří kapacitu rolí.
     *
     * @param Collection<int, Role> $selectedRoles
     */
    public function validateRolesCapacities(Collection $selectedRoles, User $user): bool
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
     * @param Collection<int, Role> $selectedRoles
     */
    public function validateRolesIncompatible(Collection $selectedRoles, Role $testRole): bool
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
     * @param Collection<int, Role> $selectedRoles
     */
    public function validateRolesRequired(Collection $selectedRoles, Role $testRole): bool
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
     * @param Collection<int, Role> $selectedRoles
     */
    public function validateRolesRegisterable(Collection $selectedRoles, User $user): bool
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
     *
     * @param Collection<int, Subevent> $selectedSubevents
     */
    public function validateSubeventsCapacities(Collection $selectedSubevents, User $user): bool
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
     * @param Collection<int, Subevent> $selectedSubevents
     */
    public function validateSubeventsIncompatible(Collection $selectedSubevents, Subevent $testSubevent): bool
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
     * @param Collection<int, Subevent> $selectedSubevents
     */
    public function validateSubeventsRequired(Collection $selectedSubevents, Subevent $testSubevent): bool
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
     * @param Collection<int, Subevent> $selectedSubevents
     */
    public function validateSubeventsRegistered(
        Collection $selectedSubevents,
        User $user,
        ?Application $editedApplication = null
    ): bool {
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
    public function validateBlockAutoRegistered(Block $block, ?int $capacity): bool
    {
        if ($capacity !== null) {
            return false;
        }

        if ($block->getProgramsCount() === 0) {
            return true;
        } elseif ($block->getProgramsCount() === 1) {
            $program = $block->getPrograms()->first();

            return ! $this->programRepository->hasOverlappingProgram($program->getId(), $program->getStart(), $program->getEnd());
        }

        return false;
    }

    /**
     * Ověří seznam e-mailů oddělených ','.
     */
    public function validateEmails(string $emails): bool
    {
        $emails = array_map(
            static fn (string $o) => trim($o),
            explode(',', $emails)
        );

        foreach ($emails as $email) {
            if (! \Nette\Utils\Validators::isEmail($email)) {
                return false;
            }
        }

        return true;
    }
}
