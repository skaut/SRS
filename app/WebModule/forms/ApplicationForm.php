<?php

namespace App\WebModule\Forms;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\ApplicationStates;
use App\Model\Enums\MaturityType;
use App\Model\Enums\Sex;
use App\Model\Enums\VariableSymbolType;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\SubeventRepository;
use App\Model\User\Application;
use App\Model\User\ApplicationRepository;
use App\Model\User\CustomInputValue\CustomCheckboxValue;
use App\Model\User\CustomInputValue\CustomInputValueRepository;
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
     * Počet vytvořených podakcí.
     * @var int
     */
    private $subeventsCount;

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

        $this->subeventsCount = $this->subeventRepository->countExplicitSubevents();

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

        $this->addCustomInputs($form);

        $this->addRolesSelect($form);

        $this->addSubeventsSelect($form);

        $this->addArrivalDeparture($form);

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
        $roles = $this->roleRepository->findRolesByIds($values['roles']);

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

        //prijezd, odjezd
        if (array_key_exists('arrival', $values))
            $this->user->setArrival($values['arrival']);

        if (array_key_exists('departure', $values))
            $this->user->setDeparture($values['departure']);

        //podakce
        $subevents = $this->subeventRepository->countExplicitSubevents() > 0
            ? $this->subeventRepository->findSubeventsByIds($values['subevents'])
            : new ArrayCollection([$this->subeventRepository->findImplicit()]);

        $application = new Application();

        $fee = $this->applicationService->countFee($roles, $subevents);

        $application->setUser($this->user);
        $application->setSubevents($subevents);
        $application->setApplicationDate(new \DateTime());
        $application->setApplicationOrder($this->applicationRepository->findLastApplicationOrder() + 1);
        $application->setMaturityDate($this->applicationService->countMaturityDate());
        $application->setVariableSymbol($this->applicationService->generateVariableSymbol($this->user));
        $application->setFee($fee);
        $application->setState($fee == 0 ? ApplicationStates::PAID : ApplicationStates::WAITING_FOR_PAYMENT);

        $this->applicationRepository->save($application);


        $this->userRepository->save($this->user);

        //prihlaseni automatickz prihlasovanych programu
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

        //odeslani potvrzovaciho mailu
        $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$this->user]), '', Template::REGISTRATION, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::EDIT_REGISTRATION_TO => $this->settingsRepository->getValue(Settings::EDIT_REGISTRATION_TO)
        ]);
    }

    /**
     * Přidá vlastní pole přihlášky.
     * @param Form $form
     */
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

    /**
     * Přidá select pro výběr podakcí.
     * @param Form $form
     */
    private function addSubeventsSelect(Form $form)
    {
        if ($this->subeventRepository->countExplicitSubevents() > 0) {
            $subeventsOptions = $this->subeventRepository->getExplicitOptionsWithCapacity();

            $subeventsSelect = $form->addMultiSelect('subevents', 'web.application_content.subevents')->setItems(
                $subeventsOptions
            )
                ->addRule(Form::FILLED, 'web.application_content.subevents_empty')
                ->addRule([$this, 'validateSubeventsCapacities'], 'web.application_content.subevents_capacity_occupied');

            //generovani chybovych hlasek pro vsechny kombinace podakci
            foreach ($this->subeventRepository->findAllExplicitOrderedByName() as $subevent) {
                $incompatibleSubevents = $subevent->getIncompatibleSubevents();
                if (count($incompatibleSubevents) > 0) {
                    $messageThis = $subevent->getName();

                    $first = TRUE;
                    $messageOthers = "";
                    foreach ($incompatibleSubevents as $incompatibleSubevent) {
                        if ($first) {
                            $messageOthers .= $incompatibleSubevent->getName();
                        }
                        else {
                            $messageOthers .= ", " . $incompatibleSubevent->getName();
                        }
                        $first = FALSE;
                    }
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

                    $first = TRUE;
                    $messageOthers = "";
                    foreach ($requiredSubevents as $requiredSubevent) {
                        if ($first)
                            $messageOthers .= $requiredSubevent->getName();
                        else
                            $messageOthers .= ", " . $requiredSubevent->getName();
                        $first = FALSE;
                    }
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
                    $form->getTranslator()->translate('web.application_content.incompatible_roles_selected', NULL,
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
            $form->setDefaults([
                'roles' => array_keys($registerableOptions)
            ]);
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
     * Přepne zobrazení polí pro příjzed a odjezd.
     * @param IControl $control
     * @return bool
     */
    public static function toggleArrivalDeparture(IControl $control)
    {
        return FALSE;
    }
}
