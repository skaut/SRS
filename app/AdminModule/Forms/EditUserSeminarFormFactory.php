<?php

declare(strict_types=1);

namespace App\AdminModule\Forms;

use App\Model\Acl\Role;
use App\Model\Acl\RoleRepository;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\CustomInput\CustomCheckbox;
use App\Model\Settings\CustomInput\CustomDate;
use App\Model\Settings\CustomInput\CustomDateTime;
use App\Model\Settings\CustomInput\CustomFile;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\CustomInput\CustomMultiSelect;
use App\Model\Settings\CustomInput\CustomSelect;
use App\Model\Settings\CustomInput\CustomText;
use App\Model\Settings\Settings;
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
use App\Services\MailService;
use App\Services\SettingsService;
use App\Utils\Helpers;
use App\Utils\Validators;
use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Http\FileUpload;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Nettrine\ORM\EntityManagerDecorator;
use Nextras\FormComponents\Controls\DateControl;
use Nextras\FormComponents\Controls\DateTimeControl;
use stdClass;
use Throwable;
use function array_key_exists;
use function assert;
use const UPLOAD_ERR_OK;

/**
 * Formulář pro úpravu podrobností o účasti uživatele na semináři.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class EditUserSeminarFormFactory
{
    use Nette\SmartObject;

    /**
     * Upravovaný uživatel.
     */
    private ?User $user = null;

    private BaseFormFactory $baseFormFactory;

    private EntityManagerDecorator $em;

    private UserRepository $userRepository;

    private CustomInputRepository $customInputRepository;

    private CustomInputValueRepository $customInputValueRepository;

    private RoleRepository $roleRepository;

    private ApplicationService $applicationService;

    private Validators $validators;

    private FilesService $filesService;

    private MailService $mailService;

    private SettingsService $settingsService;

    private AclService $aclService;

    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManagerDecorator $em,
        UserRepository $userRepository,
        CustomInputRepository $customInputRepository,
        CustomInputValueRepository $customInputValueRepository,
        RoleRepository $roleRepository,
        ApplicationService $applicationService,
        Validators $validators,
        FilesService $filesService,
        MailService $mailService,
        SettingsService $settingsService,
        AclService $aclService
    ) {
        $this->baseFormFactory            = $baseFormFactory;
        $this->em                         = $em;
        $this->userRepository             = $userRepository;
        $this->customInputRepository      = $customInputRepository;
        $this->customInputValueRepository = $customInputValueRepository;
        $this->roleRepository             = $roleRepository;
        $this->applicationService         = $applicationService;
        $this->validators                 = $validators;
        $this->filesService               = $filesService;
        $this->mailService                = $mailService;
        $this->settingsService            = $settingsService;
        $this->aclService                 = $aclService;
    }

    /**
     * Vytvoří formulář.
     */
    public function create(int $id) : Form
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        if (! $this->user->isExternalLector()) {
            $rolesSelect = $form->addMultiSelect(
                'roles',
                'admin.users.users_roles',
                $this->aclService->getRolesWithoutRolesOptionsWithCapacity([Role::GUEST, Role::UNAPPROVED])
            )
                ->addRule(Form::FILLED, 'admin.users.users_edit_roles_empty')
                ->addRule([$this, 'validateRolesNonregistered'], 'admin.users.users_edit_roles_nonregistered')
                ->addRule([$this, 'validateRolesCapacities'], 'admin.users.users_edit_roles_occupied');

            $form->addCheckbox('approved', 'admin.users.users_approved_form');

            $form->addCheckbox('attended', 'admin.users.users_attended_form');

            foreach ($this->customInputRepository->findAll() as $customInput) {
                $customInputId = 'custom' . $customInput->getId();

                switch (true) {
                    case $customInput instanceof CustomText:
                        $custom = $form->addText($customInputId, $customInput->getName());
                        /** @var ?CustomTextValue $customInputValue */
                        $customInputValue = $this->user->getCustomInputValue($customInput);
                        if ($customInputValue) {
                            $custom->setDefaultValue($customInputValue->getValue());
                        }

                        break;

                    case $customInput instanceof CustomCheckbox:
                        $custom = $form->addCheckbox($customInputId, $customInput->getName());
                        /** @var ?CustomCheckboxValue $customInputValue */
                        $customInputValue = $this->user->getCustomInputValue($customInput);
                        if ($customInputValue) {
                            $custom->setDefaultValue($customInputValue->getValue());
                        }

                        break;

                    case $customInput instanceof CustomSelect:
                        $selectOptions = $customInput->getSelectOptions();
                        $custom        = $form->addSelect($customInputId, $customInput->getName(), $selectOptions);
                        /** @var ?CustomSelectValue $customInputValue */
                        $customInputValue = $this->user->getCustomInputValue($customInput);
                        if ($customInputValue && array_key_exists($customInputValue->getValue(), $selectOptions)) {
                            $custom->setDefaultValue($customInputValue->getValue());
                        }

                        break;

                    case $customInput instanceof CustomMultiSelect:
                        $custom = $form->addMultiSelect($customInputId, $customInput->getName(), $customInput->getSelectOptions());
                        /** @var ?CustomMultiSelectValue $customInputValue */
                        $customInputValue = $this->user->getCustomInputValue($customInput);
                        if ($customInputValue) {
                            $custom->setDefaultValue($customInputValue->getValue());
                        }

                        break;

                    case $customInput instanceof CustomFile:
                        $custom = $form->addUpload($customInputId, $customInput->getName());
                        break;

                    case $customInput instanceof CustomDate:
                        $custom = new DateControl($customInput->getName());
                        /** @var ?CustomDateValue $customInputValue */
                        $customInputValue = $this->user->getCustomInputValue($customInput);
                        if ($customInputValue) {
                            $custom->setDefaultValue($customInputValue->getValue());
                        }

                        $form->addComponent($custom, $customInputId);
                        break;

                    case $customInput instanceof CustomDateTime:
                        $custom = new DateTimeControl($customInput->getName());
                        /** @var ?CustomDateTimeValue $customInputValue */
                        $customInputValue = $this->user->getCustomInputValue($customInput);
                        if ($customInputValue) {
                            $custom->setDefaultValue($customInputValue->getValue());
                        }

                        $form->addComponent($custom, $customInputId);
                        break;

                    default:
                        throw new InvalidArgumentException();
                }

                $custom->setOption('id', 'form-group-' . $customInputId);

                $rolesSelect->addCondition(self::class . '::toggleCustomInputVisibility', Helpers::getIds($customInput->getRoles()))
                    ->toggle('form-group-' . $customInputId);
            }
        }

        $form->addTextArea('about', 'admin.users.users_about_me');

        $form->addTextArea('privateNote', 'admin.users.users_private_note');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-warning');

        $form->setDefaults([
            'id' => $id,
            'roles' => $this->roleRepository->findRolesIds($this->user->getRoles()),
            'approved' => $this->user->isApproved(),
            'attended' => $this->user->isAttended(),
            'about' => $this->user->getAbout(),
            'privateNote' => $this->user->getNote(),
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
        if ($form->isSubmitted() === $form['cancel']) {
            return;
        }

        assert($this->user !== null);

        $loggedUser = $this->userRepository->findById($form->getPresenter()->user->id);

        $this->em->transactional(function () use ($values, $loggedUser) : void {
            $customInputValueChanged = false;

            if (! $this->user->isExternalLector()) {
                $selectedRoles = $this->roleRepository->findRolesByIds($values->roles);
                $this->applicationService->updateRoles($this->user, $selectedRoles, $loggedUser);

                $this->user->setApproved($values->approved);
                $this->user->setAttended($values->attended);

                foreach ($this->customInputRepository->findByRolesOrderedByPosition($this->user->getRoles()) as $customInput) {
                    $customInputId    = 'custom' . $customInput->getId();
                    $customInputValue = $this->user->getCustomInputValue($customInput);
                    $oldValue         = null;
                    $newValue         = $values->$customInputId;

                    if ($customInput instanceof CustomText) {
                        /** @var CustomTextValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomTextValue();
                        $oldValue         = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomCheckbox) {
                        /** @var CustomCheckboxValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomCheckboxValue();
                        $oldValue         = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomSelect) {
                        /** @var CustomSelectValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomSelectValue();
                        $oldValue         = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomMultiSelect) {
                        /** @var CustomMultiSelectValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomMultiSelectValue();
                        $oldValue         = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomFile) {
                        /** @var CustomFileValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomFileValue();
                        $oldValue         = $customInputValue->getValue();
                        /** @var FileUpload $newValue */
                        $newValue = $values->$customInputId;
                        if ($newValue->getError() == UPLOAD_ERR_OK) {
                            $path = $this->generatePath($newValue);
                            $this->filesService->save($newValue, $path);
                            $customInputValue->setValue($path);
                        }
                    } elseif ($customInput instanceof CustomDate) {
                        /** @var CustomDateValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomDateValue();
                        $oldValue         = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomDateTime) {
                        /** @var CustomDateTimeValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomDateTimeValue();
                        $oldValue         = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    }

                    $customInputValue->setUser($this->user);
                    $customInputValue->setInput($customInput);
                    $this->customInputValueRepository->save($customInputValue);

                    if ($oldValue !== $newValue) {
                        $customInputValueChanged = true;
                    }
                }
            }

            $this->user->setAbout($values->about);

            $this->user->setNote($values->privateNote);

            $this->userRepository->save($this->user);

            if ($customInputValueChanged) {
                $this->mailService->sendMailFromTemplate(new ArrayCollection([$this->user]), null, Template::CUSTOM_INPUT_VALUE_CHANGED, [
                    TemplateVariable::SEMINAR_NAME => $this->settingsService->getValue(Settings::SEMINAR_NAME),
                    TemplateVariable::USER => $this->user->getDisplayName(),
                ]);
            }
        });
    }

    /**
     * Ověří, že není vybrána role "Neregistrovaný".
     */
    public function validateRolesNonregistered(MultiSelectBox $field) : bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesNonregistered($selectedRoles, $this->user);
    }

    /**
     * Ověří kapacitu rolí.
     */
    public function validateRolesCapacities(MultiSelectBox $field) : bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesCapacities($selectedRoles, $this->user);
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
