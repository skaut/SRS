<?php

namespace App\WebModule\Forms;

use App\Model\ACL\RoleRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Nette;
use Nette\Application\UI\Form;

class RolesForm extends Nette\Object
{
    /** @var User */
    private $user;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var RoleRepository */
    private $roleRepository;

    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository, RoleRepository $roleRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    public function create($id, $enabled)
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $registerableNowRoles = $this->roleRepository->findAllRegisterableNow();
        $availableRoles = [];
        $availableRolesOptions = [];

        //pridani roli, ktere jsou registrovatelne
        foreach ($registerableNowRoles as $role) {
            $availableRoles[] = $role;
        }

        //pridani roli, ktere uzivatel uz ma
        foreach ($this->user->getRoles() as $role) {
            if (!in_array($role, $availableRoles)) {
                $availableRoles[] = $role;
            }
        }

        foreach ($availableRoles as $role) {
            if ($role->hasLimitedCapacity())
                $availableRolesOptions[$role->getId()] = $form->getTranslator()->translate('web.profile.role_option', null,
                    ['role' => $role->getName(), 'occupied' => $this->roleRepository->countApprovedUsersInRole($role), 'total' => $role->getCapacity()]
                );
            else
                $availableRolesOptions[$role->getId()] = $role->getName();
        }

        asort($availableRolesOptions);

        $rolesSelect = $form->addMultiSelect('roles', 'web.profile.roles')->setItems($availableRolesOptions)
            ->addRule(Form::FILLED, 'web.profile.no_role_selected')
            ->addRule([$this, 'validateRolesCapacities'], 'web.profile.capacity_occupied')
            ->addRule([$this, 'validateRolesRegisterable'], 'web.profile.role_is_not_registerable')
            ->setDisabled(!$enabled);

        foreach ($availableRoles as $role) {
            $incompatibleRoles = $role->getIncompatibleRoles();
            if ($incompatibleRoles->count() > 0) {
                $messageThis = $role->getName();

                $first = true;
                $messageOthers = "";
                foreach ($incompatibleRoles as $incompatibleRole) {
                    if ($incompatibleRole->isRegisterableNow()) {
                        if ($first)
                            $messageOthers .= $incompatibleRole->getName();
                        else
                            $messageOthers .= ", " . $incompatibleRole->getName();
                    }
                    $first = false;
                }
                $rolesSelect->addRule([$this, 'validateRolesIncompatible'],
                    $form->getTranslator()->translate('web.profile.incompatible_roles_selected', null,
                        ['role' => $messageThis, 'incompatibleRoles' => $messageOthers]
                    )
                );
            }

            $requiredRoles = $role->getRequiredRoles();
            if ($requiredRoles->count() > 0) {
                $messageThis = $role->getName();

                $first = true;
                $messageOthers = "";
                foreach ($requiredRoles as $requiredRole) {
                    if ($first)
                        $messageOthers .= $requiredRole->getName();
                    else
                        $messageOthers .= ", " . $requiredRole->getName();
                    $first = false;
                }
                $rolesSelect->addRule([$this, 'validateRolesRequired'],
                    $form->getTranslator()->translate('web.profile.required_roles_not_selected', null,
                        ['role' => $messageThis, 'requiredRoles' => $messageOthers]
                    )
                );
            }
        }

        $submitButton = $form->addSubmit('submit', 'web.profile.update_roles');

        $cancelRegistrationButton = $form->addButton('cancelRegistration', 'web.profile.cancel_registration')
            ->setAttribute('class', 'btn-danger');

        if ($enabled) {
            $submitButton
                ->setAttribute('data-toggle', 'confirmation')
                ->setAttribute('data-content', $form->getTranslator()->translate('web.profile.change_roles_confirm'));
            $cancelRegistrationButton
                ->setAttribute('data-toggle', 'confirmation')
                ->setAttribute('data-content', $form->getTranslator()->translate('web.profile.cancel_registration_confirm'));
        }
        else {
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

    public function processForm(Form $form, \stdClass $values) {
        $selectedRoles = $this->roleRepository->findRolesByIds($values['roles']);

        //pokud si uživatel přidá roli, která vyžaduje schválení, stane se neschválený
        $approved = $this->user->isApproved();
        if ($approved) {
            foreach ($selectedRoles as $role) {
                if (!$role->isApprovedAfterRegistration() && !$this->user->getRoles()->contains($role)) {
                    $approved = false;
                    break;
                }
            }
        }

        $this->user->updateRoles($selectedRoles);
        $this->user->setApproved($approved);

        $this->userRepository->save($this->user);
    }

    public function validateRolesCapacities($field, $args)
    {
        return true; // TODO
//        $field->getValue();
//            $values = $this->getComponent('roles')->getRawValue();
//            //$user = $database->getRepository('\SRS\Model\User')->findOneBy(array('id' => $this->getForm()->getHttpData()['id']));
//
//            foreach ($values as $value) {
//                //$role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $value));
//                //if ($role->usersLimit !== null) {
//                    //if ($role->countVacancies() == 0 && !$user->isInRole($role->name))
//                        return false;
//                }
//            }
//            return true;
    }

    public function validateRolesIncompatible($field, $args)
    {
        return true; // TODO
//    $database = $args[0];
//    $role = $args[1];
//
//    $values = $this->getComponent('roles')->getRawValue();
//
//    if (!in_array($role->id, $values))
//        return true;
//
//    foreach ($values as $value) {
//        $testRole = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $value));
//        if ($role != $testRole && in_array($testRole, $role->incompatibleRoles->getValues()))
//            return false;
//    }
//
//    return true;
    }

    public function validateRolesRequired($field, $args)
    {
        return true; // TODO
//    $values = $this->getComponent('roles')->getRawValue();
//
//    if (!in_array($role->id, $values))
//        return true;
//
//    $requiredRoles = $role->getAllRequiredRoles();
//    foreach ($requiredRoles as $requiredRole) {
//        if (!in_array($requiredRole->id, $values))
//            return false;
//    }
//
//    return true;
    }

    public function validateRolesRegisterable($field, $args)
    {
        return true; // TODO
//    $values = $this->getComponent('roles')->getRawValue();
//    $user = $database->getRepository('\SRS\Model\User')->findOneBy(array('id' => $this->getForm()->getHttpData()['id']));
//
//    foreach ($values as $value) {
//        $role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $value));
//        if (!$role->isRegisterableNow() && !$user->isInRole($role->name))
//            return false;
//    }
//
//    return true;
    }
}

