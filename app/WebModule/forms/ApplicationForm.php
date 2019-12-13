<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\Sex;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\CustomInput\CustomFile;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\CustomInput\CustomSelect;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\Subevent;
use App\Model\Structure\SubeventRepository;
use App\Model\User\ApplicationRepository;
use App\Model\User\CustomInputValue\CustomCheckboxValue;
use App\Model\User\CustomInputValue\CustomFileValue;
use App\Model\User\CustomInputValue\CustomInputValueRepository;
use App\Model\User\CustomInputValue\CustomSelectValue;
use App\Model\User\CustomInputValue\CustomTextValue;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ACLService;
use App\Services\ApplicationService;
use App\Services\FilesService;
use App\Services\MailService;
use App\Services\ProgramService;
use App\Services\SettingsService;
use App\Services\SkautIsService;
use App\Services\SubeventService;
use App\Utils\Validators;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use InvalidArgumentException;
use Kdyby\Translation\Translator;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Forms\IControl;
use Nette\Http\FileUpload;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Nettrine\ORM\EntityManagerDecorator;
use Skautis\Wsdl\WsdlException;
use stdClass;
use Throwable;
use Tracy\Debugger;
use Tracy\ILogger;
use function array_key_exists;
use function array_keys;
use function count;
use function in_array;

/**
 * Formulář přihlášky.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class ApplicationForm
{
    use Nette\SmartObject;

    /**
     * Přihlášený uživatel.
     * @var User
     */
    private $user;

    /** @var callable */
    public $onSkautIsError;

    /**
     * Jsou vytvořené podakce.
     * @var bool
     */
    private $subeventsExists;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var EntityManagerDecorator */
    private $em;

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

    /** @var SettingsService */
    private $settingsService;

    /** @var MailService */
    private $mailService;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var ApplicationRepository */
    private $applicationRepository;

    /** @var ACLService */
    private $ACLService;

    /** @var ApplicationService */
    private $applicationService;

    /** @var ProgramService */
    private $programService;

    /** @var Validators */
    private $validators;

    /** @var FilesService */
    private $filesService;

    /** @var SubeventService */
    private $subeventService;

    /** @var Translator */
    private $translator;


    public function __construct(
        BaseForm $baseFormFactory,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        CustomInputRepository $customInputRepository,
        CustomInputValueRepository $customInputValueRepository,
        ProgramRepository $programRepository,
        SkautIsService $skautIsService,
        SettingsService $settingsService,
        MailService $mailService,
        SubeventRepository $subeventRepository,
        ApplicationRepository $applicationRepository,
        ACLService $ACLService,
        ApplicationService $applicationService,
        ProgramService $programService,
        Validators $validators,
        FilesService $filesService,
        SubeventService $subeventService,
        Translator $translator
    ) {
        $this->baseFormFactory            = $baseFormFactory;
        $this->userRepository             = $userRepository;
        $this->roleRepository             = $roleRepository;
        $this->customInputRepository      = $customInputRepository;
        $this->customInputValueRepository = $customInputValueRepository;
        $this->programRepository          = $programRepository;
        $this->skautIsService             = $skautIsService;
        $this->settingsService            = $settingsService;
        $this->mailService                = $mailService;
        $this->subeventRepository         = $subeventRepository;
        $this->applicationRepository      = $applicationRepository;
        $this->ACLService                 = $ACLService;
        $this->applicationService         = $applicationService;
        $this->programService             = $programService;
        $this->validators                 = $validators;
        $this->filesService               = $filesService;
        $this->subeventService            = $subeventService;
        $this->translator                 = $translator;
    }

    /**
     * Vytvoří formulář.
     * @throws SettingsException
     * @throws NonUniqueResultException
     * @throws Throwable
     */
    public function create(int $id) : Form
    {
        $this->user = $this->userRepository->findById($id);

        $this->subeventsExists = $this->subeventRepository->explicitSubeventsExists();

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

        $form->addText('email', 'web.application_content.email')
            ->addRule(Form::FILLED)
            ->setDisabled();

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

        $form->addCheckbox('agreement', $this->settingsService->getValue(Settings::APPLICATION_AGREEMENT))
            ->addRule(Form::FILLED, 'web.application_content.agreement_empty');

        $form->addSubmit('submit', 'web.application_content.register');

        $form->setDefaults([
            'id' => $id,
            'sex' => $this->user->getSex(),
            'firstName' => $this->user->getFirstName(),
            'lastName' => $this->user->getLastName(),
            'nickName' => $this->user->getNickName(),
            'birthdate' => $this->user->getBirthdate(),
            'email' => $this->user->getEmail(),
            'street' => $this->user->getStreet(),
            'city' => $this->user->getCity(),
            'postcode' => $this->user->getPostcode(),
            'state' => $this->user->getState(),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        $this->em->transactional(function () use ($values) : void {
            if (array_key_exists('sex', $values)) {
                $this->user->setSex($values['sex']);
            }
            if (array_key_exists('firstName', $values)) {
                $this->user->setFirstName($values['firstName']);
            }
            if (array_key_exists('lastName', $values)) {
                $this->user->setLastName($values['lastName']);
            }
            if (array_key_exists('nickName', $values)) {
                $this->user->setNickName($values['nickName']);
            }
            if (array_key_exists('birthdate', $values)) {
                $this->user->setBirthdate($values['birthdate']);
            }

            $this->user->setStreet($values['street']);
            $this->user->setCity($values['city']);
            $this->user->setPostcode($values['postcode']);
            $this->user->setState($values['state']);

            //vlastni pole
            foreach ($this->customInputRepository->findAll() as $customInput) {
                $customInputValue = $this->user->getCustomInputValue($customInput);

                switch ($customInput->getType()) {
                    case CustomInput::TEXT:
                        $customInputValue = $customInputValue ?: new CustomTextValue();
                        $customInputValue->setValue($values['custom' . $customInput->getId()]);
                        break;

                    case CustomInput::CHECKBOX:
                        $customInputValue = $customInputValue ?: new CustomCheckboxValue();
                        $customInputValue->setValue($values['custom' . $customInput->getId()]);
                        break;

                    case CustomInput::SELECT:
                        $customInputValue = $customInputValue ?: new CustomSelectValue();
                        $customInputValue->setValue($values['custom' . $customInput->getId()]);
                        break;

                    case CustomInput::FILE:
                        $customInputValue = $customInputValue ?: new CustomFileValue();
                        $file             = $values['custom' . $customInput->getId()];
                        if ($file->size > 0) {
                            $path = $this->generatePath($file);
                            $this->filesService->save($file, $path);
                            $customInputValue->setValue($path);
                        }
                        break;
                }

                $customInputValue->setUser($this->user);
                $customInputValue->setInput($customInput);
                $this->customInputValueRepository->save($customInputValue);
            }

            //prijezd, odjezd
            if (array_key_exists('arrival', $values)) {
                $this->user->setArrival($values['arrival']);
            }

            if (array_key_exists('departure', $values)) {
                $this->user->setDeparture($values['departure']);
            }

            //role
            if (array_key_exists('roles', $values)) {
                $roles = $this->roleRepository->findRolesByIds($values['roles']);
            } else {
                $roles = $this->roleRepository->findAllRegisterableNowOrderedByName();
            }

            //podakce
            $subevents = $this->subeventRepository->explicitSubeventsExists() && ! empty($values['subevents'])
                ? $this->subeventRepository->findSubeventsByIds($values['subevents'])
                : new ArrayCollection([$this->subeventRepository->findImplicit()]);

            //aktualizace udaju ve skautIS
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
                Debugger::log($ex, ILogger::WARNING);
                $this->onSkautIsError();
            }

            //vytvoreni prihlasky
            $this->applicationService->register($this->user, $roles, $subevents, $this->user);
        });
    }

    /**
     * Přidá vlastní pole přihlášky.
     */
    private function addCustomInputs(Form $form) : void
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
                    $custom = $form->addSelect('custom' . $customInput->getId(), $customInput->getName(), $customInput->getSelectOptions());
                    break;

                case CustomInput::FILE:
                    $custom = $form->addUpload('custom' . $customInput->getId(), $customInput->getName());
                    break;

                default:
                    throw new InvalidArgumentException();
            }

            if (! $customInput->isMandatory()) {
                continue;
            }

            $custom->addRule(Form::FILLED, 'web.application_content.custom_input_empty');
        }
    }

    /**
     * Přidá select pro výběr podakcí.
     * @throws NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    private function addSubeventsSelect(Form $form) : void
    {
        if (! $this->subeventRepository->explicitSubeventsExists()) {
            return;
        }

        $subeventsOptions = $this->subeventService->getExplicitOptionsWithCapacity();

        $subeventsSelect = $form->addMultiSelect('subevents', 'web.application_content.subevents')->setItems(
            $subeventsOptions
        );
        $subeventsSelect
            ->setRequired(false)
            ->addRule([$this, 'validateSubeventsCapacities'], 'web.application_content.subevents_capacity_occupied');
        $subeventsSelect
            ->addConditionOn($form['roles'], [$this, 'toggleSubeventsRequired'])
            ->addRule(Form::FILLED, 'web.application_content.subevents_empty');

        //generovani chybovych hlasek pro vsechny kombinace podakci
        foreach ($this->subeventRepository->findAllExplicitOrderedByName() as $subevent) {
            if (! $subevent->getIncompatibleSubevents()->isEmpty()) {
                $subeventsSelect->addRule(
                    [$this, 'validateSubeventsIncompatible'],
                    $this->translator->translate(
                        'web.application_content.incompatible_subevents_selected',
                        null,
                        ['subevent' => $subevent->getName(), 'incompatibleSubevents' => $subevent->getIncompatibleSubeventsText()]
                    ),
                    [$subevent]
                );
            }

            if ($subevent->getRequiredSubeventsTransitive()->isEmpty()) {
                continue;
            }

            $subeventsSelect->addRule(
                [$this, 'validateSubeventsRequired'],
                $this->translator->translate(
                    'web.application_content.required_subevents_not_selected',
                    null,
                    ['subevent' => $subevent->getName(), 'requiredSubevents' => $subevent->getRequiredSubeventsTransitiveText()]
                ),
                [$subevent]
            );
        }
    }

    /**
     * Přidá select pro výběr rolí.
     */
    private function addRolesSelect(Form $form) : void
    {
        $registerableOptions = $this->ACLService->getRegisterableNowOptionsWithCapacity();

        $rolesSelect = $form->addMultiSelect('roles', 'web.application_content.roles')->setItems(
            $registerableOptions
        )
            ->addRule(Form::FILLED, 'web.application_content.roles_empty')
            ->addRule([$this, 'validateRolesCapacities'], 'web.application_content.roles_capacity_occupied')
            ->addRule([$this, 'validateRolesRegisterable'], 'web.application_content.role_is_not_registerable');

        //generovani chybovych hlasek pro vsechny kombinace roli
        foreach ($this->roleRepository->findAllRegisterableNowOrUsersOrderedByName($this->user) as $role) {
            if (! $role->getIncompatibleRoles()->isEmpty()) {
                $rolesSelect->addRule(
                    [$this, 'validateRolesIncompatible'],
                    $this->translator->translate(
                        'web.application_content.incompatible_roles_selected',
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
                $this->translator->translate(
                    'web.application_content.required_roles_not_selected',
                    null,
                    ['role' => $role->getName(), 'requiredRoles' => $role->getRequiredRolesTransitiveText()]
                ),
                [$role]
            );
        }

        $ids = [];
        foreach ($this->roleRepository->findAllWithArrivalDeparture() as $role) {
            $ids[] = (string) $role->getId();
        }
        $rolesSelect->addCondition(self::class . '::toggleArrivalDeparture', $ids)
            ->toggle('arrivalInput')
            ->toggle('departureInput');

        //pokud je na vyber jen jedna role, je oznacena
        if (count($registerableOptions) !== 1) {
            return;
        }

        $rolesSelect->setDisabled(true);
        $rolesSelect->setDefaultValue(array_keys($registerableOptions));
    }

    /**
     * Přidá pole pro zadání příjezdu a odjezdu.
     */
    private function addArrivalDeparture(Form $form) : void
    {
        $form->addDateTimePicker('arrival', 'web.application_content.arrival')
            ->setOption('id', 'arrivalInput');

        $form->addDateTimePicker('departure', 'web.application_content.departure')
            ->setOption('id', 'departureInput');
    }

    /**
     * Ověří kapacity podakcí.
     */
    public function validateSubeventsCapacities(MultiSelectBox $field) : bool
    {
        $selectedSubevents = $this->subeventRepository->findSubeventsByIds($field->getVaLue());
        return $this->validators->validateSubeventsCapacities($selectedSubevents, $this->user);
    }

    /**
     * Ověří kapacity rolí.
     */
    public function validateRolesCapacities(MultiSelectBox $field) : bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        return $this->validators->validateRolesCapacities($selectedRoles, $this->user);
    }

    /**
     * Ověří kompatibilitu podakcí.
     * @param Subevent[] $args
     */
    public function validateSubeventsIncompatible(MultiSelectBox $field, array $args) : bool
    {
        $selectedSubevents = $this->subeventRepository->findSubeventsByIds($field->getValue());
        $testSubevent      = $args[0];
        return $this->validators->validateSubeventsIncompatible($selectedSubevents, $testSubevent);
    }

    /**
     * Ověří výběr požadovaných podakcí.
     * @param Subevent[] $args
     */
    public function validateSubeventsRequired(MultiSelectBox $field, array $args) : bool
    {
        $selectedSubevents = $this->subeventRepository->findSubeventsByIds($field->getValue());
        $testSubevent      = $args[0];
        return $this->validators->validateSubeventsRequired($selectedSubevents, $testSubevent);
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
     * Ověří výběr požadovaných rolí.
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

    /**
     * Vrací, zda je výběr podakcí povinný pro kombinaci rolí.
     */
    public function toggleSubeventsRequired(MultiSelectBox $field) : bool
    {
        $rolesWithSubevents = $this->roleRepository->findRolesIds($this->roleRepository->findAllWithSubevents());
        foreach ($field->getValue() as $roleId) {
            if (in_array($roleId, $rolesWithSubevents)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Přepne zobrazení polí pro příjezd a odjezd.
     */
    public static function toggleArrivalDeparture(IControl $control) : bool
    {
        return false;
    }

    /**
     * Vygeneruje cestu souboru.
     */
    private function generatePath(FileUpload $file) : string
    {
        return CustomFile::PATH . '/' . Random::generate(5) . '/' . Strings::webalize($file->name, '.');
    }
}
