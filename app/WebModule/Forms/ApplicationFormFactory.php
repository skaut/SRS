<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\CustomInput\CustomCheckbox;
use App\Model\CustomInput\CustomCheckboxValue;
use App\Model\CustomInput\CustomDate;
use App\Model\CustomInput\CustomDateTime;
use App\Model\CustomInput\CustomDateTimeValue;
use App\Model\CustomInput\CustomDateValue;
use App\Model\CustomInput\CustomFile;
use App\Model\CustomInput\CustomFileValue;
use App\Model\CustomInput\CustomMultiSelect;
use App\Model\CustomInput\CustomMultiSelectValue;
use App\Model\CustomInput\CustomSelect;
use App\Model\CustomInput\CustomSelectValue;
use App\Model\CustomInput\CustomText;
use App\Model\CustomInput\CustomTextValue;
use App\Model\CustomInput\Repositories\CustomInputRepository;
use App\Model\CustomInput\Repositories\CustomInputValueRepository;
use App\Model\Enums\Sex;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\AclService;
use App\Services\ApplicationService;
use App\Services\FilesService;
use App\Services\QueryBus;
use App\Services\SkautIsService;
use App\Services\SubeventService;
use App\Utils\Helpers;
use App\Utils\Validators;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use InvalidArgumentException;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Http\FileUpload;
use Nette\Localization\Translator;
use Nextras\FormComponents\Controls\DateControl;
use Nextras\FormComponents\Controls\DateTimeControl;
use Skaut\Skautis\Wsdl\WsdlException;
use stdClass;
use Throwable;
use Tracy\Debugger;
use Tracy\ILogger;

use function array_keys;
use function assert;
use function count;
use function in_array;
use function property_exists;

use const UPLOAD_ERR_OK;

/**
 * Formulář přihlášky.
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

    public function __construct(
        private BaseFormFactory $baseFormFactory,
        private QueryBus $queryBus,
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private CustomInputRepository $customInputRepository,
        private CustomInputValueRepository $customInputValueRepository,
        private SkautIsService $skautIsService,
        private SubeventRepository $subeventRepository,
        private AclService $aclService,
        private ApplicationService $applicationService,
        private Validators $validators,
        private FilesService $filesService,
        private SubeventService $subeventService,
        private Translator $translator
    ) {
    }

    /**
     * Vytvoří formulář.
     *
     * @throws NonUniqueResultException
     * @throws Throwable
     */
    public function create(int $id): Form
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

        $form->addCheckbox('agreement', $this->queryBus->handle(new SettingStringValueQuery(Settings::APPLICATION_AGREEMENT)))
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
    public function processForm(Form $form, stdClass $values): void
    {
        $this->em->wrapInTransaction(function () use ($values): void {
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

            // role
            if (property_exists($values, 'roles')) {
                $roles = $this->roleRepository->findRolesByIds($values->roles);
            } else {
                $roles = $this->roleRepository->findFilteredRoles(true, false, false);
            }

            // vlastni pole
            foreach ($this->customInputRepository->findByRolesOrderedByPosition($roles) as $customInput) {
                $customInputId    = 'custom' . $customInput->getId();
                $customInputValue = $this->user->getCustomInputValue($customInput);

                if ($customInput instanceof CustomText) {
                    $customInputValue = $customInputValue ?: new CustomTextValue($customInput, $this->user);
                    assert($customInputValue instanceof CustomTextValue);
                    $customInputValue->setValue($values->$customInputId);
                } elseif ($customInput instanceof CustomCheckbox) {
                    $customInputValue = $customInputValue ?: new CustomCheckboxValue($customInput, $this->user);
                    assert($customInputValue instanceof CustomCheckboxValue);
                    $customInputValue->setValue($values->$customInputId);
                } elseif ($customInput instanceof CustomSelect) {
                    $customInputValue = $customInputValue ?: new CustomSelectValue($customInput, $this->user);
                    assert($customInputValue instanceof CustomSelectValue);
                    $customInputValue->setValue($values->$customInputId);
                } elseif ($customInput instanceof CustomMultiSelect) {
                    $customInputValue = $customInputValue ?: new CustomMultiSelectValue($customInput, $this->user);
                    assert($customInputValue instanceof CustomMultiSelectValue);
                    $customInputValue->setValue($values->$customInputId);
                } elseif ($customInput instanceof CustomFile) {
                    $customInputValue = $customInputValue ?: new CustomFileValue($customInput, $this->user);
                    assert($customInputValue instanceof CustomFileValue);
                    $file = $values->$customInputId;
                    assert($file instanceof FileUpload);
                    if ($file->getError() === UPLOAD_ERR_OK) {
                        $path = $this->filesService->save($file, CustomFile::PATH, true, $file->name);
                        $customInputValue->setValue($path);
                    }
                } elseif ($customInput instanceof CustomDate) {
                    $customInputValue = $customInputValue ?: new CustomDateValue($customInput, $this->user);
                    assert($customInputValue instanceof CustomDateValue);
                    $customInputValue->setValue($values->$customInputId);
                } elseif ($customInput instanceof CustomDateTime) {
                    $customInputValue = $customInputValue ?: new CustomDateTimeValue($customInput, $this->user);
                    assert($customInputValue instanceof CustomDateTimeValue);
                    $customInputValue->setValue($values->$customInputId);
                }

                $this->customInputValueRepository->save($customInputValue);
            }

            // podakce
            $subevents = $this->subeventRepository->explicitSubeventsExists() && ! empty($values->subevents)
                ? $this->subeventRepository->findSubeventsByIds($values->subevents)
                : new ArrayCollection([$this->subeventRepository->findImplicit()]);

            // aktualizace údajů ve skautIS, jen pokud nemá propojený účet
            if (! $this->user->isMember()) {
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
            }

            // vytvoreni prihlasky
            $this->applicationService->register($this->user, $roles, $subevents, $this->user);
        });
    }

    /**
     * Přidá vlastní pole přihlášky.
     */
    private function addCustomInputs(Form $form): void
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
                    $custom->setHtmlAttribute('data-show-preview', 'true');
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
                $rolesSelect = $form['roles'];
                assert($rolesSelect instanceof MultiSelectBox);
                $custom->addConditionOn($rolesSelect, self::class . '::toggleCustomInputRequired', ['id' => $customInputId, 'roles' => Helpers::getIds($customInput->getRoles())])
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
    private function addSubeventsSelect(Form $form): void
    {
        if (! $this->subeventRepository->explicitSubeventsExists()) {
            return;
        }

        $subeventsOptions = $this->subeventService->getSubeventsOptionsWithCapacity(true, true, false, false);

        $subeventsSelect = $form->addMultiSelect('subevents', 'web.application_content.subevents')->setItems(
            $subeventsOptions
        );
        $subeventsSelect->setOption('id', 'form-group-subevents');

        $rolesSelect = $form['roles'];
        assert($rolesSelect instanceof MultiSelectBox);
        $subeventsSelect->addConditionOn(
            $rolesSelect,
            self::class . '::toggleSubeventsRequired',
            Helpers::getIds($this->roleRepository->findFilteredRoles(false, true, false))
        )->addRule(Form::FILLED, 'web.application_content.subevents_empty');

        $subeventsSelect
            ->setRequired(false)
            ->addRule([$this, 'validateSubeventsCapacities'], 'web.application_content.subevents_capacity_occupied');

        // generovani chybovych hlasek pro vsechny kombinace podakci
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
    private function addRolesSelect(Form $form): void
    {
        $registerableOptions = $this->aclService->getRolesOptionsWithCapacity(true, false);

        $rolesSelect = $form->addMultiSelect('roles', 'web.application_content.roles')->setItems(
            $registerableOptions
        );

        foreach ($this->customInputRepository->findAll() as $customInput) {
            $customInputId = 'custom' . $customInput->getId();
            $rolesSelect->addCondition(self::class . '::toggleCustomInputVisibility', Helpers::getIds($customInput->getRoles()))
                ->toggle('form-group-' . $customInputId);
        }

        $rolesSelect->addRule(Form::FILLED, 'web.application_content.roles_empty')
            ->addRule([$this, 'validateRolesCapacities'], 'web.application_content.roles_capacity_occupied')
            ->addRule([$this, 'validateRolesRegisterable'], 'web.application_content.roles_not_registerable')
            ->addRule([$this, 'validateRolesMinimumAge'], 'web.application_content.roles_require_minimum_age')
            ->addRule([$this, 'validateRolesMaximumAge'], 'web.application_content.roles_require_maximum_age');

        // generovani chybovych hlasek pro vsechny kombinace roli
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

        // pokud je na vyber jen jedna role, je oznacena
        if (count($registerableOptions) === 1) {
            $rolesSelect->setDisabled();
            $rolesSelect->setDefaultValue(array_keys($registerableOptions));
        }
    }

    /**
     * Ověří kapacity podakcí.
     */
    public function validateSubeventsCapacities(MultiSelectBox $field): bool
    {
        $selectedSubevents = $this->subeventRepository->findSubeventsByIds($field->getVaLue());

        return $this->validators->validateSubeventsCapacities($selectedSubevents, $this->user);
    }

    /**
     * Ověří kapacity rolí.
     */
    public function validateRolesCapacities(MultiSelectBox $field): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesCapacities($selectedRoles, $this->user);
    }

    /**
     * Ověří kompatibilitu podakcí.
     *
     * @param Subevent[] $args
     */
    public function validateSubeventsIncompatible(MultiSelectBox $field, array $args): bool
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
    public function validateSubeventsRequired(MultiSelectBox $field, array $args): bool
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
    public function validateRolesIncompatible(MultiSelectBox $field, array $args): bool
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
    public function validateRolesRequired(MultiSelectBox $field, array $args): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        $testRole      = $args[0];

        return $this->validators->validateRolesRequired($selectedRoles, $testRole);
    }

    /**
     * Ověří registrovatelnost rolí.
     */
    public function validateRolesRegisterable(MultiSelectBox $field): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesRegisterable($selectedRoles, $this->user);
    }

    /**
     * Ověří požadovaný minimální věk.
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function validateRolesMinimumAge(MultiSelectBox $field): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesMinimumAge($selectedRoles, $this->user);
    }

    /**
     * Ověří požadovaný maximální věk.
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function validateRolesMaximumAge(MultiSelectBox $field): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesMaximumAge($selectedRoles, $this->user);
    }

    /**
     * Přepíná povinnost podakcí podle kombinace rolí.

     * @param int[] $rolesWithSubevents
     */
    public static function toggleSubeventsRequired(MultiSelectBox $field, array $rolesWithSubevents): bool
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
     * @param array<string,int[]> $customInput
     */
    public static function toggleCustomInputRequired(MultiSelectBox $field, array $customInput): bool
    {
        foreach ($field->getValue() as $roleId) {
            if (in_array($roleId, $customInput['roles'])) {
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
    public static function toggleCustomInputVisibility(MultiSelectBox $field, array $customInputRoles): bool
    {
        return false;
    }
}
