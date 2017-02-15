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
use App\Model\Settings\CustomInput\CustomInputRepository;
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

    /** @var CustomInputRepository */
    private $customInputRepository;


    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository,
                                RoleRepository $roleRepository, CustomInputRepository $customInputRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->customInputRepository = $customInputRepository;
    }

    public function create($id)
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addMultiSelect('roles', 'admin.users.users_roles',
            $this->roleRepository->getRolesWithoutRolesOptionsWithCapacity([Role::GUEST, Role::UNAPPROVED]));

        $form->addCheckbox('approved', 'admin.users.users_approved_form');

        $form->addCheckbox('attended', 'admin.users.users_attended_form');

        $form->addDateTimePicker('arrival', 'admin.users.users_arrival');

        $form->addDateTimePicker('departure', 'admin.users.users_departure');

        foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
            switch ($customInput->getType()) {
                case 'text':
                    $form->addText('custom' . $customInput->getId(), $customInput->getName());
                    break;

                case 'checkbox':
                    $form->addCheckbox('custom' . $customInput->getId(), $customInput->getName());
                    break;
            }
        }

        $form->addTextArea('privateNote', 'admin.users.users_private_note');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');


        $form->setDefaults([

        ]);


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
