<?php

namespace App\WebModule\Forms;

use App\Model\ACL\RoleRepository;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\MailService;
use Doctrine\Common\Collections\ArrayCollection;
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

    /** @var ProgramRepository */
    private $programRepository;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var MailService */
    private $mailService;


    /**
     * RolesForm constructor.
     * @param BaseForm $baseFormFactory
     * @param UserRepository $userRepository
     * @param RoleRepository $roleRepository
     * @param ProgramRepository $programRepository
     * @param SettingsRepository $settingsRepository
     * @param MailService $mailService
     */
    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository,
                                RoleRepository $roleRepository, ProgramRepository $programRepository,
                                SettingsRepository $settingsRepository, MailService $mailService)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->programRepository = $programRepository;
        $this->settingsRepository = $settingsRepository;
        $this->mailService = $mailService;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @param $enabled
     * @return Form
     */
    public function create($id, $enabled)
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $this->addRolesSelect($form, $enabled);

        $submitButton = $form->addSubmit('submit', 'web.profile.update_roles');

        $cancelRegistrationButton = $form->addSubmit('cancelRegistration', 'web.profile.cancel_registration')
            ->setAttribute('class', 'btn-danger');

        if ($enabled) {
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
     */
    public function processForm(Form $form, \stdClass $values)
    {
        if ($form['submit']->isSubmittedBy()) {
            $selectedRoles = $this->roleRepository->findRolesByIds($values['roles']);

            //pokud si uživatel přidá roli, která vyžaduje schválení, stane se neschválený
            $approved = TRUE;
            if ($approved) {
                foreach ($selectedRoles as $role) {
                    if (!$role->isApprovedAfterRegistration() && !$this->user->getRoles()->contains($role)) {
                        $approved = FALSE;
                        break;
                    }
                }
            }

            $this->user->setRoles($selectedRoles);
            $this->user->setApproved($approved);

            $this->userRepository->save($this->user);

            $this->programRepository->updateUserPrograms($this->user);

            $this->userRepository->save($this->user);

            $rolesNames = "";
            $first = TRUE;
            foreach ($this->user->getRoles() as $role) {
                if ($first) {
                    $rolesNames = $role->getName();
                    $first = FALSE;
                }
                else {
                    $rolesNames .= ', ' . $role->getName();
                }
            }

            $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$this->user]), '', Template::ROLE_CHANGED, [
                TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
                TemplateVariable::USERS_ROLES => $rolesNames
            ]);
        } elseif ($form['cancelRegistration']->isSubmittedBy()) {
            $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$this->user]), '', Template::REGISTRATION_CANCELED, [
                TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME)
            ]);

            $this->userRepository->remove($this->user);
        }
    }

    /**
     * Přidá select pro výběr rolí.
     * @param Form $form
     * @param $enabled
     */
    private function addRolesSelect(Form $form, $enabled)
    {
        $rolesSelect = $form->addMultiSelect('roles', 'web.profile.roles')->setItems(
            $this->roleRepository->getRegisterableNowOrUsersOptionsWithCapacity($this->user)
        )
            ->addRule(Form::FILLED, 'web.profile.roles_empty')
            ->addRule([$this, 'validateRolesCapacities'], 'web.profile.capacity_occupied')
            ->addRule([$this, 'validateRolesRegisterable'], 'web.profile.role_is_not_registerable')
            ->setDisabled(!$enabled);

        //generovani chybovych hlasek pro vsechny kombinace roli
        foreach ($this->roleRepository->findAllRegisterableNowOrUsersOrderedByName($this->user) as $role) {
            $incompatibleRoles = $role->getIncompatibleRoles();
            if (count($incompatibleRoles) > 0) {
                $messageThis = $role->getName();

                $first = TRUE;
                $messageOthers = "";
                foreach ($incompatibleRoles as $incompatibleRole) {
                    if ($incompatibleRole->isRegisterableNow()) {
                        if ($first)
                            $messageOthers .= $incompatibleRole->getName();
                        else
                            $messageOthers .= ", " . $incompatibleRole->getName();
                    }
                    $first = FALSE;
                }
                $rolesSelect->addRule([$this, 'validateRolesIncompatible'],
                    $form->getTranslator()->translate('web.profile.incompatible_roles_selected', NULL,
                        ['role' => $messageThis, 'incompatibleRoles' => $messageOthers]
                    ),
                    [$role]
                );
            }

            $requiredRoles = $role->getRequiredRolesTransitive();
            if (count($requiredRoles) > 0) {
                $messageThis = $role->getName();

                $first = TRUE;
                $messageOthers = "";
                foreach ($requiredRoles as $requiredRole) {
                    if ($first)
                        $messageOthers .= $requiredRole->getName();
                    else
                        $messageOthers .= ", " . $requiredRole->getName();
                    $first = FALSE;
                }
                $rolesSelect->addRule([$this, 'validateRolesRequired'],
                    $form->getTranslator()->translate('web.profile.required_roles_not_selected', NULL,
                        ['role' => $messageThis, 'requiredRoles' => $messageOthers]
                    ),
                    [$role]
                );
            }
        }
    }

    /**
     * Ověří kapacitu rolí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateRolesCapacities($field, $args)
    {
        foreach ($this->roleRepository->findRolesByIds($field->getValue()) as $role) {
            if ($role->hasLimitedCapacity()) {
                if ($this->roleRepository->countUnoccupiedInRole($role) < 1 && !$this->user->isInRole($role))
                    return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Ověří kompatibilitu rolí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateRolesIncompatible($field, $args)
    {
        $selectedRolesIds = $field->getValue();
        $testRole = $args[0];

        if (!in_array($testRole->getId(), $selectedRolesIds))
            return TRUE;

        foreach ($testRole->getIncompatibleRoles() as $incompatibleRole) {
            if (in_array($incompatibleRole->getId(), $selectedRolesIds))
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Ověří výběr vyžadovaných rolí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateRolesRequired($field, $args)
    {
        $selectedRolesIds = $field->getValue();
        $testRole = $args[0];

        if (!in_array($testRole->getId(), $selectedRolesIds))
            return TRUE;

        foreach ($testRole->getRequiredRolesTransitive() as $requiredRole) {
            if (!in_array($requiredRole->getId(), $selectedRolesIds))
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Ověří registrovatelnost rolí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateRolesRegisterable($field, $args)
    {
        foreach ($this->roleRepository->findRolesByIds($field->getValue()) as $role) {
            if (!$role->isRegisterableNow() && !$this->user->isInRole($role))
                return FALSE;
        }
        return TRUE;
    }
}

