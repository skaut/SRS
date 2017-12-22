<?php

namespace App\WebModule\Forms;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\Sex;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\SubeventRepository;
use App\Model\User\Application;
use App\Model\User\ApplicationRepository;
use App\Model\User\CustomInputValue\CustomCheckboxValue;
use App\Model\User\CustomInputValue\CustomInputValueRepository;
use App\Model\User\CustomInputValue\CustomSelectValue;
use App\Model\User\CustomInputValue\CustomTextValue;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\MailService;
use App\Services\SkautIsService;
use Doctrine\Common\Collections\ArrayCollection;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\IControl;
use Skautis\Wsdl\WsdlException;


/**
 * Formulář přihlášky.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationForm extends Nette\Object
{
    /**
     * Přihlášený uživatel.
     * @var User
     */
    private $user;

    public $onSkautIsError;

    /**
     * Jsou vytvořené podakce.
     * @var bool
     */
    private $subeventsExists;

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

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var MailService */
    private $mailService;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var ApplicationRepository */
    private $applicationRepository;

    /** @var ApplicationService */
    private $applicationService;


    /**
     * ApplicationForm constructor.
     * @param BaseForm $baseFormFactory
     * @param UserRepository $userRepository
     * @param RoleRepository $roleRepository
     * @param CustomInputRepository $customInputRepository
     * @param CustomInputValueRepository $customInputValueRepository
     * @param ProgramRepository $programRepository
     * @param SkautIsService $skautIsService
     * @param SettingsRepository $settingsRepository
     * @param MailService $mailService
     * @param SubeventRepository $subeventRepository
     * @param ApplicationRepository $applicationRepository
     * @param ApplicationService $applicationService
     */
    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository,
                                RoleRepository $roleRepository, CustomInputRepository $customInputRepository,
                                CustomInputValueRepository $customInputValueRepository,
                                ProgramRepository $programRepository, SkautIsService $skautIsService,
                                SettingsRepository $settingsRepository, MailService $mailService,
                                SubeventRepository $subeventRepository, ApplicationRepository $applicationRepository,
                                ApplicationService $applicationService)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->customInputRepository = $customInputRepository;
        $this->customInputValueRepository = $customInputValueRepository;
        $this->programRepository = $programRepository;
        $this->skautIsService = $skautIsService;
        $this->settingsRepository = $settingsRepository;
        $this->mailService = $mailService;
        $this->subeventRepository = $subeventRepository;
        $this->applicationRepository = $applicationRepository;
        $this->applicationService = $applicationService;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @return Form
     */
    public function create($id)
    {
        $this->user = $this->userRepository->findById($id);

        $this->subeventsExists = $this->subeventRepository->explicitSubeventsExists();

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $inputSex = $form->addRadioList('sex', 'web.application_content.sex', Sex::getSexOptions());
        $inputSex->getSeparatorPrototype()->setName(NULL);

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

        $this->addRolesSelect($form);

        $this->addSubeventsSelect($form);

        $this->addArrivalDeparture($form);

        $this->addCustomInputs($form);

        $form->addCheckbox('agreement', $this->settingsRepository->getValue(Settings::APPLICATION_AGREEMENT))
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

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     */
    public function processForm(Form $form, \stdClass $values)
    {
        $this->applicationRepository->getEntityManager()->transactional(function($em) use($values) {
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

            //role
            if (array_key_exists('roles', $values))
                $roles = $this->roleRepository->findRolesByIds($values['roles']);
            else
                $roles = $this->roleRepository->findAllRegisterableNowOrderedByName();

            $this->user->removeRole($this->roleRepository->findBySystemName(Role::NONREGISTERED));

            foreach ($roles as $role) {
                if (!$role->isApprovedAfterRegistration()) {
                    $this->user->setApproved(FALSE);
                    break;
                }
            }

            $this->user->setRoles($roles);

            //vlastni pole
            foreach ($this->customInputRepository->findAll() as $customInput) {
                $customInputValue = $this->user->getCustomInputValue($customInput);

                if ($customInputValue) {
                    $customInputValue->setValue($values['custom' . $customInput->getId()]);
                    continue;
                }

                switch ($customInput->getType()) {
                    case CustomInput::TEXT:
                        $customInputValue = new CustomTextValue();
                        break;

                    case CustomInput::CHECKBOX:
                        $customInputValue = new CustomCheckboxValue();
                        break;

                    case CustomInput::SELECT:
                        $customInputValue = new CustomSelectValue();
                        break;
                }
                $customInputValue->setValue($values['custom' . $customInput->getId()]);
                $customInputValue->setUser($this->user);
                $customInputValue->setInput($customInput);
                $this->customInputValueRepository->save($customInputValue);
            }

            //prijezd, odjezd
            if (array_key_exists('arrival', $values))
                $this->user->setArrival($values['arrival']);

            if (array_key_exists('departure', $values))
                $this->user->setDeparture($values['departure']);

            //podakce
            $subevents = $this->subeventRepository->explicitSubeventsExists() && !empty($values['subevents'])
                ? $this->subeventRepository->findSubeventsByIds($values['subevents'])
                : new ArrayCollection([$this->subeventRepository->findImplicit()]);

            $application = new Application();
            $fee = $this->applicationService->countFee($roles, $subevents);
            $application->setUser($this->user);
            $application->setSubevents($subevents);
            $application->setApplicationDate(new \DateTime());
            $application->setMaturityDate($this->applicationService->countMaturityDate());
            $application->setFee($fee);
            $application->setState($fee == 0 ? ApplicationState::PAID : ApplicationState::WAITING_FOR_PAYMENT);
            $this->applicationRepository->save($application);

            $application->setVariableSymbol($this->applicationService->generateVariableSymbol($application));
            $this->applicationRepository->save($application);

            $this->user->addApplication($application);
            $this->userRepository->save($this->user);

            //prihlaseni automaticke prihlasovanych programu
            $this->programRepository->updateUserPrograms($this->user);
            $this->userRepository->save($this->user);

            //aktualizace udaju ve skautis
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

            $editRegistrationTo = $this->settingsRepository->getDateValue(Settings::EDIT_REGISTRATION_TO);

            //odeslani potvrzovaciho mailu
            $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$this->user]), '', Template::REGISTRATION, [
                TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
                TemplateVariable::EDIT_REGISTRATION_TO => $editRegistrationTo !== NULL ? $editRegistrationTo->format('j. n. Y') : '',
                TemplateVariable::APPLICATION_MATURITY => $application->getMaturityDate() !== NULL ? $application->getMaturityDate()->format('j. n. Y') : '',
                TemplateVariable::APPLICATION_FEE => $application->getFee(),
                TemplateVariable::APPLICATION_VARIABLE_SYMBOL => $application->getVariableSymbol(),
                TemplateVariable::BANK_ACCOUNT => $this->settingsRepository->getValue(Settings::ACCOUNT_NUMBER)
            ]);
        });
    }

    /**
     * Přidá vlastní pole přihlášky.
     * @param Form $form
     */
    private function addCustomInputs(Form $form)
    {
        foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
            switch ($customInput->getType()) {
                case CustomInput::TEXT:
                    $custom = $form->addText('custom' . $customInput->getId(), $customInput->getName());
                    break;

                case CustomInput::CHECKBOX:
                    $custom = $form->addCheckbox('custom' . $customInput->getId(), $customInput->getName());
                    break;

                case CustomInput::SELECT:
                    $custom = $form->addSelect('custom' . $customInput->getId(), $customInput->getName(), $customInput->prepareSelectOptions());
                    break;
            }

            if ($customInput->isMandatory())
                $custom->addRule(Form::FILLED, 'web.application_content.custom_input_empty');
        }
    }

    /**
     * Přidá select pro výběr podakcí.
     * @param Form $form
     */
    private function addSubeventsSelect(Form $form)
    {
        if ($this->subeventRepository->explicitSubeventsExists()) {
            $subeventsOptions = $this->subeventRepository->getExplicitOptionsWithCapacity();

            $subeventsSelect = $form->addMultiSelect('subevents', 'web.application_content.subevents')->setItems(
                $subeventsOptions
            );
            $subeventsSelect
                ->setRequired(FALSE)
                ->addRule([$this, 'validateSubeventsCapacities'], 'web.application_content.subevents_capacity_occupied');
            $subeventsSelect
                ->addConditionOn($form['roles'], [$this, 'toggleSubeventsRequired'])
                ->addRule(Form::FILLED, 'web.application_content.subevents_empty');

            //generovani chybovych hlasek pro vsechny kombinace podakci
            foreach ($this->subeventRepository->findAllExplicitOrderedByName() as $subevent) {
                $incompatibleSubevents = $subevent->getIncompatibleSubevents();
                if (count($incompatibleSubevents) > 0) {
                    $messageThis = $subevent->getName();

                    $incompatibleSubeventsNames = [];
                    foreach ($incompatibleSubevents as $incompatibleSubevent) {
                        $incompatibleSubeventsNames[] = $incompatibleSubevent->getName();
                    }
                    $messageOthers = implode(', ', $incompatibleSubeventsNames);

                    $subeventsSelect->addRule([$this, 'validateSubeventsIncompatible'],
                        $form->getTranslator()->translate('web.application_content.incompatible_subevents_selected', NULL,
                            ['subevent' => $messageThis, 'incompatibleSubevents' => $messageOthers]
                        ),
                        [$subevent]
                    );
                }

                $requiredSubevents = $subevent->getRequiredSubeventsTransitive();
                if (count($requiredSubevents) > 0) {
                    $messageThis = $subevent->getName();

                    $requiredSubeventsNames = [];
                    foreach ($requiredSubevents as $requiredSubevent) {
                        $requiredSubeventsNames[] = $requiredSubevent->getName();
                    }
                    $messageOthers = implode(', ', $requiredSubeventsNames);

                    $subeventsSelect->addRule([$this, 'validateSubeventsRequired'],
                        $form->getTranslator()->translate('web.application_content.required_subevents_not_selected', NULL,
                            ['subevent' => $messageThis, 'requiredSubevents' => $messageOthers]
                        ),
                        [$subevent]
                    );
                }
            }
        }
    }

    /**
     * Přidá select pro výběr rolí.
     * @param Form $form
     */
    private function addRolesSelect(Form $form)
    {
        $registerableOptions = $this->roleRepository->getRegisterableNowOptionsWithCapacity();

        $rolesSelect = $form->addMultiSelect('roles', 'web.application_content.roles')->setItems(
            $registerableOptions
        )
            ->addRule(Form::FILLED, 'web.application_content.roles_empty')
            ->addRule([$this, 'validateRolesCapacities'], 'web.application_content.roles_capacity_occupied')
            ->addRule([$this, 'validateRolesRegisterable'], 'web.application_content.role_is_not_registerable');

        //generovani chybovych hlasek pro vsechny kombinace roli
        foreach ($this->roleRepository->findAllRegisterableNowOrUsersOrderedByName($this->user) as $role) {
            $incompatibleRoles = $role->getIncompatibleRoles();
            if (count($incompatibleRoles) > 0) {
                $messageThis = $role->getName();

                $incompatibleRolesNames = [];
                foreach ($incompatibleRoles as $incompatibleRole) {
                    $incompatibleRolesNames[] = $incompatibleRole->getName();
                }
                $messageOthers = implode(', ', $incompatibleRolesNames);

                $rolesSelect->addRule([$this, 'validateRolesIncompatible'],
                    $form->getTranslator()->translate('web.application_content.incompatible_roles_selected', NULL,
                        ['role' => $messageThis, 'incompatibleRoles' => $messageOthers]
                    ),
                    [$role]
                );
            }

            $requiredRoles = $role->getRequiredRolesTransitive();
            if (count($requiredRoles) > 0) {
                $messageThis = $role->getName();

                $requiredRolesNames = [];
                foreach ($requiredRoles as $requiredRole) {
                    $requiredRolesNames[] = $requiredRole->getName();
                }
                $messageOthers = implode(', ', $requiredRolesNames);

                $rolesSelect->addRule([$this, 'validateRolesRequired'],
                    $form->getTranslator()->translate('web.application_content.required_roles_not_selected', NULL,
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

        //pokud je na vyber jen jedna role, je oznacena
        if (count($registerableOptions) == 1) {
            $rolesSelect->setDisabled(TRUE);
            $rolesSelect->setDefaultValue(array_keys($registerableOptions));
        }
    }

    /**
     * Přidá pole pro zadání příjezdu a odjezdu.
     * @param Form $form
     */
    private function addArrivalDeparture(Form $form)
    {
        $form->addDateTimePicker('arrival', 'web.application_content.arrival')
            ->setOption('id', 'arrivalInput');

        $form->addDateTimePicker('departure', 'web.application_content.departure')
            ->setOption('id', 'departureInput');
    }

    /**
     * Ověří kapacity podakcí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateSubeventsCapacities($field, $args)
    {
        foreach ($this->subeventRepository->findSubeventsByIds($field->getValue()) as $subevent) {
            if ($subevent->hasLimitedCapacity()) {
                if ($this->subeventRepository->countUnoccupiedInSubevent($subevent) < 1)
                    return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Ověří kapacity rolí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateRolesCapacities($field, $args)
    {
        foreach ($this->roleRepository->findRolesByIds($field->getValue()) as $role) {
            if ($role->hasLimitedCapacity()) {
                if ($this->roleRepository->countUnoccupiedInRole($role) < 1)
                    return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Ověří kompatibilitu podakcí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateSubeventsIncompatible($field, $args)
    {
        $selectedSubeventsIds = $field->getValue();
        $testSubevent = $args[0];

        if (!in_array($testSubevent->getId(), $selectedSubeventsIds))
            return TRUE;

        foreach ($testSubevent->getIncompatibleSubevents() as $incompatibleSubevent) {
            if (in_array($incompatibleSubevent->getId(), $selectedSubeventsIds))
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Ověří výběr požadovaných podakcí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateSubeventsRequired($field, $args)
    {
        $selectedSubeventsIds = $field->getValue();
        $testSubevent = $args[0];

        if (!in_array($testSubevent->getId(), $selectedSubeventsIds))
            return TRUE;

        foreach ($testSubevent->getRequiredSubeventsTransitive() as $requiredSubevent) {
            if (!in_array($requiredSubevent->getId(), $selectedSubeventsIds))
                return FALSE;
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
     * Ověří výběr požadovaných rolí.
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
            if (!$role->isRegisterableNow())
                return FALSE;
        }
        return TRUE;
    }

    /**
     * Vrací, zda je výběr podakcí povinný pro kombinaci rolí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function toggleSubeventsRequired($field, $args)
    {
        $rolesWithSubevents = $this->roleRepository->findRolesIds($this->roleRepository->findAllWithSubevents());
        foreach ($field->getValue() as $roleId)
            if (in_array($roleId, $rolesWithSubevents))
                return TRUE;
        return FALSE;
    }

    /**
     * Přepne zobrazení polí pro příjzed a odjezd.
     * @param IControl $control
     * @return bool
     */
    public static function toggleArrivalDeparture(IControl $control)
    {
        return FALSE;
    }
}
