<?php

namespace App\WebModule\Forms;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Program\ProgramRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Utils\Validators;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro změnu rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class RolesForm extends Nette\Object
{
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


    /**
     * RolesForm constructor.
     * @param BaseForm $baseFormFactory
     * @param UserRepository $userRepository
     * @param RoleRepository $roleRepository
     * @param ApplicationService $applicationService
     */
    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository,
                                RoleRepository $roleRepository, ApplicationService $applicationService,
                                Validators $validators)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->applicationService = $applicationService;
        $this->validators = $validators;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @return Form
     * @throws \App\Model\Settings\SettingsException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function create($id)
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
            ->setDisabled(!$this->applicationService->isAllowedEditRegistration($this->user));

        foreach ($this->roleRepository->findAllRegisterableNowOrUsersOrderedByName($this->user) as $role) {
            if (!$role->getIncompatibleRoles()->isEmpty()) {
                $rolesSelect->addRule([$this, 'validateRolesIncompatible'],
                    $form->getTranslator()->translate('web.profile.incompatible_roles_selected', NULL,
                        ['role' => $role->getName(), 'incompatibleRoles' => $role->getIncompatibleRolesText()]
                    ),
                    [$role]
                );
            }
            if (!$role->getRequiredRolesTransitive()->isEmpty()) {
                $rolesSelect->addRule([$this, 'validateRolesRequired'],
                    $form->getTranslator()->translate('web.profile.required_roles_not_selected', NULL,
                        ['role' => $role->getName(), 'requiredRoles' => $role->getRequiredRolesTransitiveText()]
                    ),
                    [$role]
                );
            }
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
            'roles' => $this->roleRepository->findRolesIds($this->user->getRoles())
        ]);
        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     * @throws \Throwable
     */
    public function processForm(Form $form, \stdClass $values)
    {
        if ($form['submit']->isSubmittedBy()) {
            $selectedRoles = $this->roleRepository->findRolesByIds($values['roles']);

            if ($selectedRoles->count() == $this->user->getRoles()->count()) {
                $selectedRolesArray = $selectedRoles->map(function (Role $role) {return $role->getId();})->toArray();
                $usersRolesArray = $this->user->getRoles()->map(function (Role $role) {return $role->getId();})->toArray();

                if (array_diff($selectedRolesArray, $usersRolesArray) === array_diff($usersRolesArray, $selectedRolesArray))
                    return;
            }

            $this->applicationService->updateRoles($this->user, $selectedRoles, $this->user);
        }
        elseif ($form['cancelRegistration']->isSubmittedBy()) {
            $this->applicationService->cancelRegistration($this->user, $this->user);
        }
    }

    /**
     * Ověří kapacitu rolí.
     * @param $field
     * @param $args
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function validateRolesCapacities($field, $args)
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        return $this->validators->validateRolesCapacities($selectedRoles, $this->user);
    }

    /**
     * Ověří kompatibilitu rolí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateRolesIncompatible($field, $args)
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        $testRole = $args[0];

        return $this->validators->validateRolesIncompatible($selectedRoles, $testRole);
    }

    /**
     * Ověří výběr vyžadovaných rolí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateRolesRequired($field, $args)
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        $testRole = $args[0];

        return $this->validators->validateRolesRequired($selectedRoles, $testRole);
    }

    /**
     * Ověří registrovatelnost rolí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateRolesRegisterable($field, $args)
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        return $this->validators->validateRolesRegisterable($selectedRoles, $this->user);
    }
}
