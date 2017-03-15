<?php

namespace App\WebModule\Forms;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\Sex;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\User\CustomInputValue\CustomCheckboxValue;
use App\Model\User\CustomInputValue\CustomInputValueRepository;
use App\Model\User\CustomInputValue\CustomTextValue;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\SkautIsService;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\IControl;
use Skautis\Wsdl\WsdlException;


class ApplicationForm extends Nette\Object
{
    /** @var User */
    private $user;

    public $onSkautIsError;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var CustomInputRepository */
    private $customInputRepository;

    /** @var CustomInputValueRepository */
    private $customInputValueRepository;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var SkautIsService */
    private $skautIsService;


    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository,
                                RoleRepository $roleRepository, CustomInputRepository $customInputRepository,
                                CustomInputValueRepository $customInputValueRepository,
                                ProgramRepository $programRepository, SkautIsService $skautIsService)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->customInputRepository = $customInputRepository;
        $this->customInputValueRepository = $customInputValueRepository;
        $this->programRepository = $programRepository;
        $this->skautIsService = $skautIsService;
    }

    public function create($id)
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $inputSex = $form->addRadioList('sex', 'web.application_content.sex', Sex::getSexOptions());
        $inputSex->getSeparatorPrototype()->setName(null);

        $inputFirstName = $form->addText('firstName', 'web.application_content.firstname')
            ->addRule(Form::FILLED, 'web.application_content.firstname_empty');

        $inputLastName = $form->addText('lastName', 'web.application_content.lastname')
            ->addRule(Form::FILLED, 'web.application_content.lastname_empty');

        $inputNickName = $form->addText('nickName', 'web.application_content.nickname');

        $inputBirthdate = $form->addDatePicker('birthdate', 'web.application_content.birthdate')
            ->addRule(Form::FILLED, 'web.application_content.birthdate_empty');

        if ($this->user->isMember()) {
            $inputSex->setDisabled();
            $inputFirstName->setDisabled();
            $inputLastName->setDisabled();
            $inputNickName->setDisabled();
            $inputBirthdate->setDisabled();
        }

        $form->addText('street', 'web.application_content.street')
            ->addRule(Form::FILLED, 'web.application_content.street_empty')
            ->addRule(Form::PATTERN, 'web.application_content.street_format', '^(.*[^0-9]+) (([1-9][0-9]*)/)?([1-9][0-9]*[a-cA-C]?)$');

        $form->addText('city', 'web.application_content.city')
            ->addRule(Form::FILLED, 'web.application_content.city_empty');

        $form->addText('postcode', 'web.application_content.postcode')
            ->addRule(Form::FILLED, 'web.application_content.postcode_empty')
            ->addRule(Form::PATTERN, 'web.application_content.postcode_format', '^\d{3} ?\d{2}$');

        $form->addText('state', 'web.application_content.state')
            ->addRule(Form::FILLED, 'web.application_content.state_empty');

        $this->addCustomInputs($form);

        $this->addRolesSelect($form);

        $this->addArrivalDeparture($form);

        $form->addCheckbox('agreement', 'web.application_content.agreement')
            ->addRule(Form::FILLED, 'web.application_content.agreement_empty');

        $form->addSubmit('submit', 'web.application_content.register');

        $form->setDefaults([
            'id' => $id,
            'sex' => $this->user->getSex(),
            'firstName' => $this->user->getFirstName(),
            'lastName' => $this->user->getLastName(),
            'nickName' => $this->user->getNickName(),
            'birthdate' => $this->user->getBirthdate(),
            'street' => $this->user->getStreet(),
            'city' => $this->user->getCity(),
            'postcode' => $this->user->getPostcode(),
            'state' => $this->user->getState()
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values)
    {
        if (array_key_exists('sex', $values))
            $this->user->setSex($values['sex']);
        if (array_key_exists('firstName', $values))
            $this->user->setFirstName($values['firstName']);
        if (array_key_exists('lastName', $values))
            $this->user->setLastName($values['lastName']);
        if (array_key_exists('nickName', $values))
            $this->user->setNickName($values['nickName']);
        if (array_key_exists('birthdate', $values))
            $this->user->setBirthdate($values['birthdate']);

        $this->user->setStreet($values['street']);
        $this->user->setCity($values['city']);
        $this->user->setPostcode($values['postcode']);
        $this->user->setState($values['state']);


        $roles = $this->roleRepository->findRolesByIds($values['roles']);

        $this->user->removeRole($this->roleRepository->findBySystemName(Role::NONREGISTERED));

        foreach ($roles as $role) {
            if (!$role->isApprovedAfterRegistration()) {
                $this->user->setApproved(false);
                break;
            }
        }

        $this->user->setRoles($roles);

        foreach ($this->customInputRepository->findAll() as $customInput) {
            switch ($customInput->getType()) {
                case 'text':
                    $customInputValue = new CustomTextValue();
                    break;
                case 'checkbox':
                    $customInputValue = new CustomCheckboxValue();
                    break;
            }
            $customInputValue->setValue($values['custom' . $customInput->getId()]);
            $customInputValue->setUser($this->user);
            $customInputValue->setInput($customInput);
            $this->customInputValueRepository->save($customInputValue);
        }

        if (array_key_exists('arrival', $values))
            $this->user->setArrival($values['arrival']);

        if (array_key_exists('departure', $values))
            $this->user->setDeparture($values['departure']);


        $this->userRepository->save($this->user);

        $this->programRepository->updateUserPrograms($this->user);

        $this->userRepository->save($this->user);

        try {
            $this->skautIsService->updatePersonBasic(
                $this->user->getSkautISPersonId(),
                $this->user->getSex(),
                $this->user->getBirthdate(),
                $this->user->getFirstName(),
                $this->user->getLastName(),
                $this->user->getNickName()
            );

            $this->skautIsService->updatePersonAddress(
                $this->user->getSkautISPersonId(),
                $this->user->getStreet(),
                $this->user->getCity(),
                $this->user->getPostcode(),
                $this->user->getState()
            );
        } catch (WsdlException $ex) {
            $this->onSkautIsError();
        }
    }

    private function addCustomInputs(Form $form)
    {
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
    }

    private function addRolesSelect(Form $form)
    {
        $rolesSelect = $form->addMultiSelect('roles', 'web.application_content.roles')->setItems(
            $this->roleRepository->getRegisterableNowOptionsWithCapacity()
        )
            ->addRule(Form::FILLED, 'web.application_content.roles_empty')
            ->addRule([$this, 'validateRolesCapacities'], 'web.application_content.capacity_occupied')
            ->addRule([$this, 'validateRolesRegisterable'], 'web.application_content.role_is_not_registerable');

        //generovani chybovych hlasek pro vsechny kombinace roli
        foreach ($this->roleRepository->findAllRegisterableNowOrUsersOrderedByName($this->user) as $role) {
            $incompatibleRoles = $role->getIncompatibleRoles();
            if (count($incompatibleRoles) > 0) {
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
                    $form->getTranslator()->translate('web.application_content.incompatible_roles_selected', null,
                        ['role' => $messageThis, 'incompatibleRoles' => $messageOthers]
                    ),
                    [$role]
                );
            }

            $requiredRoles = $role->getRequiredRolesTransitive();
            if (count($requiredRoles) > 0) {
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
                    $form->getTranslator()->translate('web.application_content.required_roles_not_selected', null,
                        ['role' => $messageThis, 'requiredRoles' => $messageOthers]
                    ),
                    [$role]
                );
            }
        }

        $ids = [];
        foreach ($this->roleRepository->findAllWithArrivalDeparture() as $role) {
            $ids[] = (string)$role->getId();
        }
        $rolesSelect->addCondition(ApplicationForm::class . '::toggleArrivalDeparture', $ids)
            ->toggle('arrivalInput')
            ->toggle('departureInput');
    }

    private function addArrivalDeparture(Form $form)
    {
        $form->addDateTimePicker('arrival', 'web.application_content.arrival')
            ->setOption('id', 'arrivalInput');

        $form->addDateTimePicker('departure', 'web.application_content.departure')
            ->setOption('id', 'departureInput');
    }

    public function validateRolesCapacities($field, $args)
    {
        foreach ($this->roleRepository->findRolesByIds($field->getValue()) as $role) {
            if ($role->hasLimitedCapacity()) {
                if ($this->roleRepository->countUnoccupiedInRole($role) < 1)
                    return false;
            }
        }
        return true;
    }

    public function validateRolesIncompatible($field, $args)
    {
        $selectedRolesIds = $field->getValue();
        $testRole = $args[0];

        if (!in_array($testRole->getId(), $selectedRolesIds))
            return true;

        foreach ($testRole->getIncompatibleRoles() as $incompatibleRole) {
            if (in_array($incompatibleRole->getId(), $selectedRolesIds))
                return false;
        }

        return true;
    }

    public function validateRolesRequired($field, $args)
    {
        $selectedRolesIds = $field->getValue();
        $testRole = $args[0];

        if (!in_array($testRole->getId(), $selectedRolesIds))
            return true;

        foreach ($testRole->getRequiredRolesTransitive() as $requiredRole) {
            if (!in_array($requiredRole->getId(), $selectedRolesIds))
                return false;
        }

        return true;
    }

    public function validateRolesRegisterable($field, $args)
    {
        foreach ($this->roleRepository->findRolesByIds($field->getValue()) as $role) {
            if (!$role->isRegisterableNow())
                return false;
        }
        return true;
    }

    public static function toggleArrivalDeparture(IControl $control)
    {
        return false;
    }
}
