<?php

namespace App\WebModule\Forms;

use App\Model\ACL\RoleRepository;
use Nette\Application\UI\Form;

class RolesFormFactory
{
    /**
     * @var BaseFormFactory
     */
    private $baseFormFactory;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    public function __construct(BaseFormFactory $baseFormFactory, RoleRepository $roleRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->roleRepository = $roleRepository;
    }

    public function create($user, $enabled)
    {
        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $registerableNowRoles = $this->roleRepository->findRegisterableNowRoles();
        $availableRoles = [];
        $availableRolesOptions = [];

        //pridani roli, ktere jsou registrovatelne
        foreach ($registerableNowRoles as $role) {
            $availableRoles[] = $role;
        }

        //pridani roli, ktere uzivatel uz ma
        foreach ($user->getRoles() as $role) {
            if (!in_array($role, $availableRoles)) {
                $availableRoles[] = $role;
            }
        }

        foreach ($availableRoles as $role) {
            if ($role->getCapacity() === null)
                $availableRolesOptions[$role->getId()] = $role->getName();
            else
                $availableRolesOptions[$role->getId()] = $form->getTranslator()->translate('web.profile.role_option', null,
                    ['role' => $role->getName(), 'occupied' => $role->getApprovedUsers()->count(), 'total' => $role->getCapacity()]
                );
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

        return $form;
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

