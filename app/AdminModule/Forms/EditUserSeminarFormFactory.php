<?php

declare(strict_types=1);

namespace App\AdminModule\Forms;

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
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\AclService;
use App\Services\ApplicationService;
use App\Services\FilesService;
use App\Services\IMailService;
use App\Services\QueryBus;
use App\Services\UserService;
use App\Utils\Helpers;
use App\Utils\Validators;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Http\FileUpload;
use Nextras\FormComponents\Controls\DateControl;
use Nextras\FormComponents\Controls\DateTimeControl;
use stdClass;
use Throwable;

use function array_key_exists;
use function assert;
use function basename;
use function json_encode;

use const JSON_THROW_ON_ERROR;
use const UPLOAD_ERR_OK;

/**
 * Formulář pro úpravu podrobností o účasti uživatele na semináři
 */
class EditUserSeminarFormFactory
{
    use Nette\SmartObject;

    /**
     * Upravovaný uživatel
     */
    private ?User $user = null;

    public function __construct(
        private BaseFormFactory $baseFormFactory,
        private QueryBus $queryBus,
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private CustomInputRepository $customInputRepository,
        private CustomInputValueRepository $customInputValueRepository,
        private RoleRepository $roleRepository,
        private ApplicationService $applicationService,
        private Validators $validators,
        private FilesService $filesService,
        private IMailService $mailService,
        private AclService $aclService,
        private UserService $userService
    ) {
    }

    /**
     * Vytvoří formulář
     */
    public function create(int $id): Form
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        if (! $this->user->isExternalLector()) {
            $rolesSelect = $form->addMultiSelect(
                'roles',
                'admin.users.users_roles',
                $this->aclService->getRolesWithoutRolesOptionsWithCapacity([Role::GUEST, Role::UNAPPROVED])
            );

            $form->addCheckbox('approved', 'admin.users.users_approved_form');

            $form->addCheckbox('attended', 'admin.users.users_attended_form');

            foreach ($this->customInputRepository->findAll() as $customInput) {
                $customInputId = 'custom' . $customInput->getId();

                switch (true) {
                    case $customInput instanceof CustomText:
                        $custom = $form->addText($customInputId, $customInput->getName());

                        $customInputValue = $this->user->getCustomInputValue($customInput);
                        if ($customInputValue) {
                            assert($customInputValue instanceof CustomTextValue);
                            $custom->setDefaultValue($customInputValue->getValue());
                        }

                        break;

                    case $customInput instanceof CustomCheckbox:
                        $custom = $form->addCheckbox($customInputId, $customInput->getName());

                        $customInputValue = $this->user->getCustomInputValue($customInput);
                        if ($customInputValue) {
                            assert($customInputValue instanceof CustomCheckboxValue);
                            $custom->setDefaultValue($customInputValue->getValue());
                        }

                        break;

                    case $customInput instanceof CustomSelect:
                        $selectOptions = $customInput->getSelectOptions();
                        $custom        = $form->addSelect($customInputId, $customInput->getName(), $selectOptions);

                        $customInputValue = $this->user->getCustomInputValue($customInput);
                        if ($customInputValue) {
                            assert($customInputValue instanceof CustomSelectValue);
                            if (array_key_exists($customInputValue->getValue(), $selectOptions)) {
                                $custom->setDefaultValue($customInputValue->getValue());
                            }
                        }

                        break;

                    case $customInput instanceof CustomMultiSelect:
                        $custom = $form->addMultiSelect($customInputId, $customInput->getName(), $customInput->getSelectOptions());

                        $customInputValue = $this->user->getCustomInputValue($customInput);
                        if ($customInputValue) {
                            assert($customInputValue instanceof CustomMultiSelectValue);
                            $custom->setDefaultValue($customInputValue->getValue());
                        }

                        break;

                    case $customInput instanceof CustomFile:
                        $custom = $form->addUpload($customInputId, $customInput->getName());
                        $custom->setHtmlAttribute('data-show-preview', 'true');

                        $customInputValue = $this->user->getCustomInputValue($customInput);
                        if ($customInputValue) {
                            assert($customInputValue instanceof CustomFileValue);
                            if ($customInputValue->getValue() !== null) {
                                $file = $customInputValue->getValue();
                                $custom->setHtmlAttribute('data-initial-preview', json_encode([$file], JSON_THROW_ON_ERROR))
                                    ->setHtmlAttribute('data-initial-preview-file-type', 'other')
                                    ->setHtmlAttribute('data-initial-preview-config', json_encode([
                                        [
                                            'caption' => basename($file),
                                            'downloadUrl' => $file,
                                        ],
                                    ]));
                            }
                        }

                        break;

                    case $customInput instanceof CustomDate:
                        $custom = new DateControl($customInput->getName());

                        $customInputValue = $this->user->getCustomInputValue($customInput);
                        if ($customInputValue) {
                            assert($customInputValue instanceof CustomDateValue);
                            $custom->setDefaultValue($customInputValue->getValue());
                        }

                        $form->addComponent($custom, $customInputId);
                        break;

                    case $customInput instanceof CustomDateTime:
                        $custom = new DateTimeControl($customInput->getName());

                        $customInputValue = $this->user->getCustomInputValue($customInput);
                        if ($customInputValue) {
                            assert($customInputValue instanceof CustomDateTimeValue);
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

            $rolesSelect->addRule(Form::FILLED, 'admin.users.users_edit_roles_empty')
                ->addRule([$this, 'validateRolesNonregistered'], 'admin.users.users_edit_roles_nonregistered')
                ->addRule([$this, 'validateRolesCapacities'], 'admin.users.users_edit_roles_occupied');
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
     * Zpracuje formulář
     *
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values): void
    {
        if ($form->isSubmitted() === $form['cancel']) {
            return;
        }

        $loggedUser = $this->userRepository->findById($form->getPresenter()->user->id);

        $this->em->wrapInTransaction(function () use ($values, $loggedUser): void {
            $customInputValueChanged = false;

            if (! $this->user->isExternalLector()) {
                $selectedRoles = $this->roleRepository->findRolesByIds($values->roles);
                $this->applicationService->updateRoles($this->user, $selectedRoles, $loggedUser);

                $this->userService->setApproved($this->user, $values->approved);
                $this->user->setAttended($values->attended);

                foreach ($this->customInputRepository->findByRolesOrderedByPosition($this->user->getRoles()) as $customInput) {
                    $customInputId    = 'custom' . $customInput->getId();
                    $customInputValue = $this->user->getCustomInputValue($customInput);
                    $oldValue         = null;
                    $newValue         = $values->$customInputId;

                    if ($customInput instanceof CustomText) {
                        $customInputValue = $customInputValue ?: new CustomTextValue($customInput, $this->user);
                        assert($customInputValue instanceof CustomTextValue);
                        $oldValue = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomCheckbox) {
                        $customInputValue = $customInputValue ?: new CustomCheckboxValue($customInput, $this->user);
                        assert($customInputValue instanceof CustomCheckboxValue);
                        $oldValue = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomSelect) {
                        $customInputValue = $customInputValue ?: new CustomSelectValue($customInput, $this->user);
                        assert($customInputValue instanceof CustomSelectValue);
                        $oldValue = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomMultiSelect) {
                        $customInputValue = $customInputValue ?: new CustomMultiSelectValue($customInput, $this->user);
                        assert($customInputValue instanceof CustomMultiSelectValue);
                        $oldValue = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomFile) {
                        $customInputValue = $customInputValue ?: new CustomFileValue($customInput, $this->user);
                        assert($customInputValue instanceof CustomFileValue);
                        $oldValue = $customInputValue->getValue();
                        assert($newValue instanceof FileUpload);
                        if ($newValue->getError() === UPLOAD_ERR_OK) {
                            if ($oldValue !== null) {
                                $this->filesService->delete($oldValue);
                            }

                            $path = $this->filesService->save($newValue, CustomFile::PATH, true, $newValue->name);
                            $customInputValue->setValue($path);
                        }
                    } elseif ($customInput instanceof CustomDate) {
                        $customInputValue = $customInputValue ?: new CustomDateValue($customInput, $this->user);
                        assert($customInputValue instanceof CustomDateValue);
                        $oldValue = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomDateTime) {
                        $customInputValue = $customInputValue ?: new CustomDateTimeValue($customInput, $this->user);
                        assert($customInputValue instanceof CustomDateTimeValue);
                        $oldValue = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    }

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
                assert($this->user instanceof User);
                $this->mailService->sendMailFromTemplate(new ArrayCollection([$this->user]), null, Template::CUSTOM_INPUT_VALUE_CHANGED, [
                    TemplateVariable::SEMINAR_NAME => $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME)),
                    TemplateVariable::USER => $this->user->getDisplayName(),
                ]);
            }
        });
    }

    /**
     * Ověří, že není vybrána role "Neregistrovaný"
     */
    public function validateRolesNonregistered(MultiSelectBox $field): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesNonregistered($selectedRoles, $this->user);
    }

    /**
     * Ověří kapacitu rolí
     */
    public function validateRolesCapacities(MultiSelectBox $field): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesCapacities($selectedRoles, $this->user);
    }

    /**
     * Přepíná zobrazení vlastních polí podle kombinace rolí
     * Je nutná, na výsledku nezáleží (používá se javascript funkce)
     *
     * @param int[] $customInputRoles
     */
    public static function toggleCustomInputVisibility(MultiSelectBox $field, array $customInputRoles): bool
    {
        return false;
    }
}
