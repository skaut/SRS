<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Enums\ApplicationState;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingDateTimeValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\AclService;
use App\Services\ApplicationService;
use App\Services\QueryBus;
use App\Utils\Validators;
use DateTimeImmutable;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Localization\Translator;
use stdClass;
use Throwable;

trait RoleFormTrait
{
    /**
     * Přidá do formuláře samotného případné chybové hlášky věkových omezení rolí.
     *
     * @throws Throwable
     */
    public function validateRolesAgeLimits(Form $form, stdClass $values): void
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($values->roles);
        $minWarnings= []; 
        $this->validators->validateRolesMinimumAge($selectedRoles, $this->user, $minWarnings);
        foreach($minWarnings as $error) {
            $form->addError($error);
        }
        // Max a Min se  kontroluje zvlášť, protože rozdíl může být jednou vůči FROM_DATE a pak TO_DATE
        $maxWarnings= [];
        $this->validators->validateRolesMaximumAge($selectedRoles, $this->user, $maxWarnings);
        foreach($maxWarnings as $error) {
            $form->addError($error);
        }
    }

    /**
     * Ověří požadovaný minimální věk.
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function validateRolesMinimumAge(MultiSelectBox $field): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesMinimumAge($selectedRoles, $this->user);
    }

    /**
     * Ověří požadovaný maximální věk.
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function validateRolesMaximumAge(MultiSelectBox $field): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesMaximumAge($selectedRoles, $this->user);
    }

    /**
     * Ověří kapacitu rolí.
     */
    public function validateRolesCapacities(MultiSelectBox $field): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesCapacities($selectedRoles, $this->user);
    }

    /**
     * Ověří kompatibilitu rolí.
     *
     * @param Role[] $args
     */
    public function validateRolesIncompatible(MultiSelectBox $field, array $args): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        $testRole      = $args[0];

        return $this->validators->validateRolesIncompatible($selectedRoles, $testRole);
    }

    /**
     * Ověří registrovatelnost rolí.
     */
    public function validateRolesRegisterable(MultiSelectBox $field): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesRegisterable($selectedRoles, $this->user);
    }

    /**
     * Ověří výběr vyžadovaných rolí.
     *
     * @param Role[] $args
     */
    public function validateRolesRequired(MultiSelectBox $field, array $args): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        $testRole      = $args[0];

        return $this->validators->validateRolesRequired($selectedRoles, $testRole);
    }

}
