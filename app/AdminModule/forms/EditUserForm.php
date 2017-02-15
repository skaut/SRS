<?php

namespace App\AdminModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\ACL\Permission;
use App\Model\ACL\PermissionRepository;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\CMS\PageRepository;
use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\CategoryRepository;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Kdyby\Translation\Translator;
use Nette;
use Nette\Application\UI\Form;

class EditUserForm extends Nette\Object
{
    /** @var User */
    private $user;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var RoleRepository */
    private $roleRepository;


    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository,
                                RoleRepository $roleRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    public function create($id)
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

//        $form->addHidden('id');
//
//        $form->addText('name', 'admin.acl.roles_name')
//            ->addRule(Form::FILLED, 'admin.acl.roles_name_empty')
//            ->addRule(Form::IS_NOT_IN, 'admin.acl.roles_name_exists', $this->roleRepository->findOthersNames($id))
//            ->addRule(Form::NOT_EQUAL, 'admin.acl.roles_name_reserved', 'test');
//
//        $form->addCheckbox('registerable', 'admin.acl.roles_registerable_form');
//
//        $form->addDateTimePicker('registerableFrom', 'admin.acl.roles_registerable_from')
//            ->setAttribute('data-toggle', 'tooltip')
//            ->setAttribute('title', $form->getTranslator()->translate('admin.acl.roles_registerable_from_note'));
//
//        $form->addDateTimePicker('registerableTo', 'admin.acl.roles_registerable_to')
//            ->setAttribute('data-toggle', 'tooltip')
//            ->setAttribute('title', $form->getTranslator()->translate('admin.acl.roles_registerable_to_note'));
//
//        $form->addText('capacity', 'admin.acl.roles_capacity')
//            ->setAttribute('data-toggle', 'tooltip')
//            ->setAttribute('title', $form->getTranslator()->translate('admin.acl.roles_capacity_note'))
//            ->addCondition(Form::FILLED)
//            ->addRule(Form::INTEGER, 'admin.acl.roles_capacity_format')
//            ->addRule(Form::MIN, 'admin.acl.roles_capacity_low', $this->roleRepository->countApprovedUsersInRole($this->role));
//
//        $form->addCheckbox('approvedAfterRegistration', 'admin.acl.roles_approved_after_registration');
//
//        $form->addCheckbox('syncedWithSkautIs', 'admin.acl.roles_synced_with_skaut_is');
//
//        $form->addCheckbox('displayArrivalDeparture', 'admin.acl.roles_display_arrival_departure');
//
//        $form->addText('fee', 'admin.acl.roles_fee')
//            ->addCondition(Form::FILLED)
//            ->addRule(Form::INTEGER, 'admin.acl.roles_fee_format');
//
//        $pagesOptions = $this->pageRepository->getPagesOptions();
//
//        $allowedPages = $form->addMultiSelect('pages', 'admin.acl.roles_pages', $pagesOptions);
//
//        $form->addSelect('redirectAfterLogin', 'admin.acl.roles_redirect_after_login', $pagesOptions)
//            ->setPrompt('')
//            ->setAttribute('title', $form->getTranslator()->translate('admin.acl.roles_redirect_after_login_note'))
//            ->addCondition(Form::FILLED)
//            ->addRule([$this, 'validateRedirectAllowed'], 'admin.acl.roles_redirect_after_login_restricted', [$allowedPages]);
//
//
//        $rolesOptions = $this->roleRepository->getRolesWithoutRoleOptions($this->role->getId());
//
//        $incompatibleRolesSelect = $form->addMultiSelect('incompatibleRoles', 'admin.acl.roles_incompatible_roles', $rolesOptions);
//
//        $requiredRolesSelect = $form->addMultiSelect('requiredRoles', 'admin.acl.roles_required_roles', $rolesOptions);
//
//        $incompatibleRolesSelect
//            ->addCondition(Form::FILLED)
//            ->addRule([$this, 'validateIncompatibleAndRequiredCollision'],
//                'admin.acl.roles_incompatible_collision', [$incompatibleRolesSelect, $requiredRolesSelect]);
//
//        $requiredRolesSelect
//            ->addCondition(Form::FILLED)
//            ->addRule([$this, 'validateIncompatibleAndRequiredCollision'],
//                'admin.acl.roles_required_collision', [$incompatibleRolesSelect, $requiredRolesSelect]);
//
//        $form->addSubmit('submit', 'admin.common.save');
//
//        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');
//
//
//        $form->setDefaults([
//            'id' => $id,
//            'name' => $this->role->getName(),
//            'registerable' => $this->role->isRegisterable(),
//            'registerableFrom' => $this->role->getRegisterableFrom(),
//            'registerableTo' => $this->role->getRegisterableTo(),
//            'capacity' => $this->role->getCapacity(),
//            'approvedAfterRegistration' => $this->role->isApprovedAfterRegistration(),
//            'syncedWithSkautIs' => $this->role->isSyncedWithSkautIS(),
//            'displayArrivalDeparture' => $this->role->isDisplayArrivalDeparture(),
//            'fee' => $this->role->getFee(),
//            'permissions' => $this->permissionRepository->findPermissionsIds($this->role->getPermissions()),
//            'pages' => $this->pageRepository->findPagesSlugs($this->role->getPages()),
//            'redirectAfterLogin' => $this->role->getRedirectAfterLogin(),
//            'incompatibleRoles' => $this->roleRepository->findRolesIds($this->role->getIncompatibleRoles()),
//            'requiredRoles' => $this->roleRepository->findRolesIds($this->role->getRequiredRoles())
//        ]);


        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values) {
        $capacity = $values['capacity'] !== '' ? $values['capacity'] : null;

        $this->role->setName($values['name']);
        $this->role->setRegisterable($values['registerable']);
        $this->role->setRegisterableFrom($values['registerableFrom']);
        $this->role->setRegisterableTo($values['registerableTo']);
        $this->role->setCapacity($capacity);
        $this->role->setApprovedAfterRegistration($values['approvedAfterRegistration']);
        $this->role->setSyncedWithSkautIS($values['syncedWithSkautIs']);
        $this->role->setDisplayArrivalDeparture($values['displayArrivalDeparture']);
        $this->role->setFee($values['fee']);

        $this->roleRepository->save($this->role);
    }
}
