<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Acl\Role;
use App\Model\Acl\RoleRepository;
use App\Model\Enums\Sex;
use App\Model\Settings\CustomInput\CustomCheckbox;
use App\Model\Settings\CustomInput\CustomDate;
use App\Model\Settings\CustomInput\CustomDateTime;
use App\Model\Settings\CustomInput\CustomFile;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\CustomInput\CustomMultiSelect;
use App\Model\Settings\CustomInput\CustomSelect;
use App\Model\Settings\CustomInput\CustomText;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Structure\Subevent;
use App\Model\Structure\SubeventRepository;
use App\Model\User\CustomInputValue\CustomCheckboxValue;
use App\Model\User\CustomInputValue\CustomDateTimeValue;
use App\Model\User\CustomInputValue\CustomDateValue;
use App\Model\User\CustomInputValue\CustomFileValue;
use App\Model\User\CustomInputValue\CustomInputValueRepository;
use App\Model\User\CustomInputValue\CustomMultiSelectValue;
use App\Model\User\CustomInputValue\CustomSelectValue;
use App\Model\User\CustomInputValue\CustomTextValue;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\AclService;
use App\Services\ApplicationService;
use App\Services\FilesService;
use App\Services\SettingsService;
use App\Services\SkautIsService;
use App\Services\SubeventService;
use App\Utils\Helpers;
use App\Utils\Validators;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use InvalidArgumentException;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Http\FileUpload;
use Nette\Localization\ITranslator;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Nettrine\ORM\EntityManagerDecorator;
use Nextras\FormComponents\Controls\DateControl;
use Nextras\FormComponents\Controls\DateTimeControl;
use Skautis\Wsdl\WsdlException;
use stdClass;
use Throwable;
use Tracy\Debugger;
use Tracy\ILogger;
use function array_keys;
use function count;
use function in_array;
use function property_exists;
use const UPLOAD_ERR_OK;

/**
 * Formulář přihlášky.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class ApplicationFormFactory
{
    use Nette\SmartObject;

    /**
     * Přihlášený uživatel.
     */
    private ?User $user = null;

    /** @var callable[] */
    public array $onSkautIsError = [];

    private BaseFormFactory $baseFormFactory;

    private EntityManagerDecorator $em;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private CustomInputRepository $customInputRepository;

    private CustomInputValueRepository $customInputValueRepository;

    private SkautIsService $skautIsService;

    private SettingsService $settingsService;

    private SubeventRepository $subeventRepository;

    private AclService $aclService;

    private ApplicationService $applicationService;

    private Validators $validators;

    private FilesService $filesService;

    private SubeventService $subeventService;

    private ITranslator $translator;

    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManagerDecorator $em,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        CustomInputRepository $customInputRepository,
        CustomInputValueRepository $customInputValueRepository,
        SkautIsService $skautIsService,
        SettingsService $settingsService,
        SubeventRepository $subeventRepository,
        AclService $aclService,
        ApplicationService $applicationService,
        Validators $validators,
        FilesService $filesService,
        SubeventService $subeventService,
        ITranslator $translator
    ) {
        $this->baseFormFactory            = $baseFormFactory;
        $this->em                         = $em;
        $this->userRepository             = $userRepository;
        $this->roleRepository             = $roleRepository;
        $this->customInputRepository      = $customInputRepository;
        $this->customInputValueRepository = $customInputValueRepository;
        $this->skautIsService             = $skautIsService;
        $this->settingsService            = $settingsService;
        $this->subeventRepository         = $subeventRepository;
        $this->aclService                 = $aclService;
        $this->applicationService         = $applicationService;
        $this->validators                 = $validators;
        $this->filesService               = $filesService;
        $this->subeventService            = $subeventService;
        $this->translator                 = $translator;
    }

    /**
     * Vytvoří formulář.
     *
     * @throws SettingsException
     * @throws NonUniqueResultException
     * @throws Throwable
     */
    public function create(int $id) : Form
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $inputSex = $form->addRadioList('sex', 'web.application_content.sex', Sex::getSexOptions());

        $inputFirstName = $form->addText('firstName', 'web.application_content.firstname')
            ->addRule(Form::FILLED, 'web.application_content.firstname_empty');

        $inputLastName = $form->addText('lastName', 'web.application_content.lastname')
            ->addRule(Form::FILLED, 'web.application_content.lastname_empty');

        $inputNickName = $form->addText('nickName', 'web.application_content.nickname');

        $inputBirthdateDate = new DateControl('web.application_content.birthdate');
        $inputBirthdateDate->addRule(Form::FILLED, 'web.application_content.birthdate_empty');
        $form->addComponent($inputBirthdateDate, 'birthdate');

        if ($this->user->isMember()) {
            $inputSex->setDisabled();
            $inputFirstName->setDisabled();
            $inputLastName->setDisabled();
            $inputNickName->setDisabled();
            $inputBirthdateDate->setDisabled();
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
     *
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        $this->em->transactional(function () use ($values) : void {
            if (property_exists($values, 'sex')) {
                $this->user->setSex($values->sex);
            }

            if (property_exists($values, 'firstName')) {
                $this->user->setFirstName($values->firstName);
            }

            if (property_exists($values, 'lastName')) {
                $this->user->setLastName($values->lastName);
            }

            if (property_exists($values, 'nickName')) {
                $this->user->setNickName($values->nickName);
            }

            if (property_exists($values, 'birthdate')) {
                $this->user->setBirthdate($values->birthdate);
            }

            $this->user->setStreet($values->street);
            $this->user->setCity($values->city);
            $this->user->setPostcode($values->postcode);
            $this->user->setState($values->state);

            //role
            if (property_exists($values, 'roles')) {
                $roles = $this->roleRepository->findRolesByIds($values->roles);
            } else {
                $roles = $this->roleRepository->findFilteredRoles(true, false, false);
            }

            //vlastni pole
            foreach ($this->customInputRepository->findByRolesOrderedByPosition($roles) as $customInput) {
                $customInputValue = $this->user->getCustomInputValue($customInput);
                $customInputName  = 'custom' . $customInput->getId();

                if ($customInput instanceof CustomText) {
                    /** @var CustomTextValue $customInputValue */
                    $customInputValue = $customInputValue ?: new CustomTextValue();
                    $customInputValue->setValue($values->$customInputName);
                } elseif ($customInput instanceof CustomCheckbox) {
                    /** @var CustomCheckboxValue $customInputValue */
                    $customInputValue = $customInputValue ?: new CustomCheckboxValue();
                    $customInputValue->setValue($values->$customInputName);
                } elseif ($customInput instanceof CustomSelect) {
                    /** @var CustomSelectValue $customInputValue */
                    $customInputValue = $customInputValue ?: new CustomSelectValue();
                    $customInputValue->setValue($values->$customInputName);
                } elseif ($customInput instanceof CustomMultiSelect) {
                    /** @var CustomMultiSelectValue $customInputValue */
                    $customInputValue = $customInputValue ?: new CustomMultiSelectValue();
                    $customInputValue->setValue($values->$customInputName);
                } elseif ($customInput instanceof CustomFile) {
                    /** @var CustomFileValue $customInputValue */
                    $customInputValue = $customInputValue ?: new CustomFileValue();
                    /** @var FileUpload $file */
                    $file = $values->$customInputName;
                    if ($file->getError() == UPLOAD_ERR_OK) {
                        $path = $this->generatePath($file);
                        $this->filesService->save($file, $path);
                        $customInputValue->setValue($path);
                    }
                } elseif ($customInput instanceof CustomDate) {
                    /** @var CustomDateValue $customInputValue */
                    $customInputValue = $customInputValue ?: new CustomDateValue();
                    $customInputValue->setValue($values->$customInputName);
                } elseif ($customInput instanceof CustomDateTime) {
                    /** @var CustomDateTimeValue $customInputValue */
                    $customInputValue = $customInputValue ?: new CustomDateTimeValue();
                    $customInputValue->setValue($values->$customInputName);
                }

                $customInputValue->setUser($this->user);
                $customInputValue->setInput($customInput);
                $this->customInputValueRepository->save($customInputValue);
            }

            //podakce
            $subevents = $this->subeventRepository->explicitSubeventsExists() && ! empty($values->subevents)
                ? $this->subeventRepository->findSubeventsByIds($values->subevents)
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
            $customInputId   = 'custom' . $customInput->getId();
            $customInputName = $customInput->getName();

            switch (true) {
                case $customInput instanceof CustomText:
                    $custom = $form->addText($customInputId, $customInputName);
                    break;

                case $customInput instanceof CustomCheckbox:
                    $custom = $form->addCheckbox($customInputId, $customInputName);
                    break;

                case $customInput instanceof CustomSelect:
                    $custom = $form->addSelect($customInputId, $customInputName, $customInput->getSelectOptions());
                    break;

                case $customInput instanceof CustomMultiSelect:
                    $custom = $form->addMultiSelect($customInputId, $customInputName, $customInput->getSelectOptions());
                    break;

                case $customInput instanceof CustomFile:
                    $custom = $form->addUpload($customInputId, $customInputName);
                    break;

                case $customInput instanceof CustomDate:
                    $custom = new DateControl($customInputName);
                    $form->addComponent($custom, $customInputId);
                    break;

                case $customInput instanceof CustomDateTime:
                    $custom = new DateTimeControl($customInputName);
                    $form->addComponent($custom, $customInputId);
                    break;

                default:
                    throw new InvalidArgumentException();
            }

            $custom->setOption('id', 'form-group-' . $customInputId);

            if ($customInput->isMandatory()) {
                /** @var MultiSelectBox $rolesSelect */
                $rolesSelect = $form['roles'];
                $custom->addConditionOn($rolesSelect, self::class . '::toggleCustomInputRequired', [$customInputId, Helpers::getIds($customInput->getRoles())])
                    ->addRule(Form::FILLED, 'web.application_content.custom_input_empty');
            }
        }
    }

    /**
     * Přidá select pro výběr podakcí.
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function addSubeventsSelect(Form $form) : void
    {
        if (! $this->subeventRepository->explicitSubeventsExists()) {
            return;
        }

        $subeventsOptions = $this->subeventService->getSubeventsOptionsWithCapacity(true, true, false, false);

        $subeventsSelect = $form->addMultiSelect('subevents', 'web.application_content.subevents')->setItems(
            $subeventsOptions
        );
        $subeventsSelect->setOption('id', 'form-group-subevents');
        $subeventsSelect
            ->setRequired(false)
            ->addRule([$this, 'validateSubeventsCapacities'], 'web.application_content.subevents_capacity_occupied');

        /** @var MultiSelectBox $rolesSelect */
        $rolesSelect = $form['roles'];
        $subeventsSelect->addConditionOn(
            $rolesSelect,
            self::class . '::toggleSubeventsRequired',
            Helpers::getIds($this->roleRepository->findFilteredRoles(false, true, false))
        )->addRule(Form::FILLED, 'web.application_content.subevents_empty');

        //generovani chybovych hlasek pro vsechny kombinace podakci
        foreach ($this->subeventRepository->findFilteredSubevents(true, false, false, false) as $subevent) {
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

            if (! $subevent->getRequiredSubeventsTransitive()->isEmpty()) {
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
    }

    /**
     * Přidá select pro výběr rolí.
     */
    private function addRolesSelect(Form $form) : void
    {
        $registerableOptions = $this->aclService->getRolesOptionsWithCapacity(true, false);

        $rolesSelect = $form->addMultiSelect('roles', 'web.application_content.roles')->setItems(
            $registerableOptions
        )
            ->addRule(Form::FILLED, 'web.application_content.roles_empty')
            ->addRule([$this, 'validateRolesCapacities'], 'web.application_content.roles_capacity_occupied')
            ->addRule([$this, 'validateRolesRegisterable'], 'web.application_content.roles_not_registerable')
            ->addRule([$this, 'validateRolesMinimumAge'], 'web.application_content.roles_require_minimum_age');

        //generovani chybovych hlasek pro vsechny kombinace roli
        foreach ($this->roleRepository->findFilteredRoles(true, false, true, $this->user) as $role) {
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

            if (! $role->getRequiredRolesTransitive()->isEmpty()) {
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
        }

        foreach ($this->customInputRepository->findAll() as $customInput) {
            $customInputId = 'custom' . $customInput->getId();
            $rolesSelect->addCondition(self::class . '::toggleCustomInputVisibility', Helpers::getIds($customInput->getRoles()))
                ->toggle('form-group-' . $customInputId);
        }

        //pokud je na vyber jen jedna role, je oznacena
        if (count($registerableOptions) === 1) {
            $rolesSelect->setDisabled(true);
            $rolesSelect->setDefaultValue(array_keys($registerableOptions));
        }
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
     *
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
     *
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
     *
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
     *
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
     * Ověří požadovaný minimální věk.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function validateRolesMinimumAge(MultiSelectBox $field) : bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesMinimumAge($selectedRoles, $this->user);
    }

    /**
     * Přepíná povinnost podakcí podle kombinace rolí.

     * @param int[] $rolesWithSubevents
     */
    public static function toggleSubeventsRequired(MultiSelectBox $field, array $rolesWithSubevents) : bool
    {
        foreach ($field->getValue() as $roleId) {
            if (in_array($roleId, $rolesWithSubevents)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Přepíná povinnost vlastních polí podle kombinace rolí.
     *
     * @param array $customInput
     */
    public static function toggleCustomInputRequired(MultiSelectBox $field, array $customInput) : bool
    {
        $customInputRoles = $customInput[1];
        foreach ($field->getValue() as $roleId) {
            if (in_array($roleId, $customInputRoles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Přepíná zobrazení vlastních polí podle kombinace rolí.
     * Je nutná, na výsledku nezáleží (používá se javascript funkce).
     *
     * @param int[] $customInputRoles
     */
    public static function toggleCustomInputVisibility(MultiSelectBox $field, array $customInputRoles) : bool
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
