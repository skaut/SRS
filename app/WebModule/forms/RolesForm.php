<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Settings\SettingsException;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Utils\Validators;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\MultiSelectBox;

/**
 * Formulář pro změnu rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class RolesForm
{
    use Nette\SmartObject;

    /**
     * Přihlášený uživatel.
     * @var User
     */
    private $user;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var ApplicationService */
    private $applicationService;

    /** @var Validators */
    private $validators;


    public function __construct(
        BaseForm $baseFormFactory,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        ApplicationService $applicationService,
        Validators $validators
    ) {
        $this->baseFormFactory    = $baseFormFactory;
        $this->userRepository     = $userRepository;
        $this->roleRepository     = $roleRepository;
        $this->applicationService = $applicationService;
        $this->validators         = $validators;
    }

    /**
     * Vytvoří formulář.
     * @throws SettingsException
     * @throws \Throwable
     */
    public function create(int $id) : Form
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $rolesSelect = $form->addMultiSelect('roles', 'web.profile.roles')->setItems(
            $this->roleRepository->getRegisterableNowOrUsersOptionsWithCapacity($this->user)
        )
            ->addRule(Form::FILLED, 'web.profile.roles_empty')
            ->addRule([$this, 'validateRolesCapacities'], 'web.profile.roles_capacity_occupied')
            ->addRule([$this, 'validateRolesRegisterable'], 'web.profile.role_is_not_registerable')
            ->setDisabled(! $this->applicationService->isAllowedEditRegistration($this->user));

        foreach ($this->roleRepository->findAllRegisterableNowOrUsersOrderedByName($this->user) as $role) {
            if (! $role->getIncompatibleRoles()->isEmpty()) {
                $rolesSelect->addRule(
                    [$this, 'validateRolesIncompatible'],
                    $form->getTranslator()->translate(
                        'web.profile.incompatible_roles_selected',
                        null,
                        ['role' => $role->getName(), 'incompatibleRoles' => $role->getIncompatibleRolesText()]
                    ),
                    [$role]
                );
            }
            if ($role->getRequiredRolesTransitive()->isEmpty()) {
                continue;
            }

            $rolesSelect->addRule(
                [$this, 'validateRolesRequired'],
                $form->getTranslator()->translate(
                    'web.profile.required_roles_not_selected',
                    null,
                    ['role' => $role->getName(), 'requiredRoles' => $role->getRequiredRolesTransitiveText()]
                ),
                [$role]
            );
        }

        $submitButton = $form->addSubmit('submit', 'web.profile.change_roles');

        $cancelRegistrationButton = $form->addSubmit('cancelRegistration', 'web.profile.cancel_registration')
            ->setAttribute('class', 'btn-danger');

        if ($this->applicationService->isAllowedEditRegistration($this->user)) {
            $submitButton
                ->setAttribute('data-toggle', 'confirmation')
                ->setAttribute('data-content', $form->getTranslator()->translate('web.profile.change_roles_confirm'));
            $cancelRegistrationButton
                ->setAttribute('data-toggle', 'confirmation')
                ->setAttribute('data-content', $form->getTranslator()->translate('web.profile.cancel_registration_confirm'));
        } else {
            $submitButton
                ->setDisabled()
                ->setAttribute('data-toggle', 'tooltip')
                ->setAttribute('title', $form->getTranslator()->translate('web.profile.change_roles_disabled'));
            $cancelRegistrationButton
                ->setDisabled()
                ->setAttribute('data-toggle', 'tooltip')
                ->setAttribute('title', $form->getTranslator()->translate('web.profile.cancel_registration_disabled'));
        }
        $form->setDefaults([
            'id' => $id,
            'roles' => $this->roleRepository->findRolesIds($this->user->getRoles()),
        ]);
        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }

    /**
     * Zpracuje formulář.
     * @throws \Throwable
     */
    public function processForm(Form $form, \stdClass $values) : void
    {
        if ($form['submit']->isSubmittedBy()) {
            $selectedRoles = $this->roleRepository->findRolesByIds($values['roles']);
            $this->applicationService->updateRoles($this->user, $selectedRoles, $this->user);
        } elseif ($form['cancelRegistration']->isSubmittedBy()) {
            $this->applicationService->cancelRegistration($this->user, ApplicationState::CANCELED, $this->user);
        }
    }

    /**
     * Ověří kapacitu rolí.
     */
    public function validateRolesCapacities(MultiSelectBox $field) : bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        return $this->validators->validateRolesCapacities($selectedRoles, $this->user);
    }

    /**
     * Ověří kompatibilitu rolí.
     * @param Role[] $args
     */
    public function validateRolesIncompatible(MultiSelectBox $field, array $args) : bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        $testRole      = $args[0];

        return $this->validators->validateRolesIncompatible($selectedRoles, $testRole);
    }

    /**
     * Ověří výběr vyžadovaných rolí.
     * @param Role[] $args
     */
    public function validateRolesRequired(MultiSelectBox $field, array $args) : bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        $testRole      = $args[0];

        return $this->validators->validateRolesRequired($selectedRoles, $testRole);
    }

    /**
     * Ověří registrovatelnost rolí.
     */
    public function validateRolesRegisterable(MultiSelectBox $field) : bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        return $this->validators->validateRolesRegisterable($selectedRoles, $this->user);
    }
}
