<?php

declare(strict_types=1);

namespace App\AdminModule\Forms;

use App\Model\Acl\Role;
use App\Model\Acl\RoleRepository;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\CustomInput\CustomCheckbox;
use App\Model\Settings\CustomInput\CustomFile;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\CustomInput\CustomSelect;
use App\Model\Settings\CustomInput\CustomText;
use App\Model\Settings\Settings;
use App\Model\User\CustomInputValue\CustomCheckboxValue;
use App\Model\User\CustomInputValue\CustomFileValue;
use App\Model\User\CustomInputValue\CustomInputValueRepository;
use App\Model\User\CustomInputValue\CustomSelectValue;
use App\Model\User\CustomInputValue\CustomTextValue;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\AclService;
use App\Services\ApplicationService;
use App\Services\FilesService;
use App\Services\MailService;
use App\Services\SettingsService;
use App\Utils\Validators;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Http\FileUpload;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Nettrine\ORM\EntityManagerDecorator;
use Nextras\FormComponents\Controls\DateTimeControl;
use stdClass;
use Throwable;
use function property_exists;
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
     *
     * @var User
     */
    private $user;

    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var EntityManagerDecorator */
    private $em;

    /** @var UserRepository */
    private $userRepository;

    /** @var CustomInputRepository */
    private $customInputRepository;

    /** @var CustomInputValueRepository */
    private $customInputValueRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var ApplicationService */
    private $applicationService;

    /** @var Validators */
    private $validators;

    /** @var FilesService*/
    private $filesService;

    /** @var MailService */
    private $mailService;

    /** @var SettingsService */
    private $settingsService;

    /** @var AclService */
    private $aclService;

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
            $form->addMultiSelect(
                'roles',
                'admin.users.users_roles',
                $this->aclService->getRolesWithoutRolesOptionsWithCapacity([Role::GUEST, Role::UNAPPROVED])
            )
                ->addRule(Form::FILLED, 'admin.users.users_edit_roles_empty')
                ->addRule([$this, 'validateRolesNonregistered'], 'admin.users.users_edit_roles_nonregistered')
                ->addRule([$this, 'validateRolesCapacities'], 'admin.users.users_edit_roles_occupied');

            $form->addCheckbox('approved', 'admin.users.users_approved_form');

            $form->addCheckbox('attended', 'admin.users.users_attended_form');

            if ($this->user->hasDisplayArrivalDepartureRole()) {
                $arrivalDateTime = new DateTimeControl('admin.users.users_arrival');
                $form->addComponent($arrivalDateTime, 'arrival');
                $departureDateTime = new DateTimeControl('admin.users.users_departure');
                $form->addComponent($departureDateTime, 'departure');
            }

            foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
                if ($customInput instanceof CustomText) {
                    $custom = $form->addText('custom' . $customInput->getId(), $customInput->getName());
                    /** @var ?CustomTextValue $customInputValue */
                    $customInputValue = $this->user->getCustomInputValue($customInput);
                    if ($customInputValue) {
                        $custom->setDefaultValue($customInputValue->getValue());
                    }
                } elseif ($customInput instanceof CustomCheckbox) {
                    $custom = $form->addCheckbox('custom' . $customInput->getId(), $customInput->getName());
                    /** @var ?CustomCheckboxValue $customInputValue */
                    $customInputValue = $this->user->getCustomInputValue($customInput);
                    if ($customInputValue) {
                        $custom->setDefaultValue($customInputValue->getValue());
                    }
                } elseif ($customInput instanceof CustomSelect) {
                    $custom = $form->addSelect('custom' . $customInput->getId(), $customInput->getName(), $customInput->getSelectOptions());
                    /** @var ?CustomSelectValue $customInputValue */
                    $customInputValue = $this->user->getCustomInputValue($customInput);
                    if ($customInputValue) {
                        $custom->setDefaultValue($customInputValue->getValue());
                    }
                } elseif ($customInput instanceof CustomFile) {
                    $form->addUpload('custom' . $customInput->getId(), $customInput->getName());
                }
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
            'arrival' => $this->user->getArrival(),
            'departure' => $this->user->getDeparture(),
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

        $loggedUser = $this->userRepository->findById($form->getPresenter()->user->id);

        $this->em->transactional(function () use ($values, $loggedUser) : void {
            $customInputValueChanged = false;

            if (! $this->user->isExternalLector()) {
                $selectedRoles = $this->roleRepository->findRolesByIds($values->roles);
                $this->applicationService->updateRoles($this->user, $selectedRoles, $loggedUser);

                $this->user->setApproved($values->approved);
                $this->user->setAttended($values->attended);

                foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
                    $customInputValue = $this->user->getCustomInputValue($customInput);
                    $customInputName  = 'custom' . $customInput->getId();
                    $oldValue         = null;
                    $newValue         = null;

                    if ($customInput instanceof CustomText) {
                        /** @var CustomTextValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomTextValue();
                        $oldValue         = $customInputValue->getValue();
                        $newValue         = $values->$customInputName;
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomCheckbox) {
                        /** @var CustomCheckboxValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomCheckboxValue();
                        $oldValue         = $customInputValue->getValue();
                        $newValue         = $values->$customInputName;
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomSelect) {
                        /** @var CustomSelectValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomSelectValue();
                        $oldValue         = $customInputValue->getValue();
                        $newValue         = $values->$customInputName;
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomFile) {
                        /** @var CustomFileValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomFileValue();
                        $oldValue         = $customInputValue->getValue();
                        /** @var FileUpload $newValue */
                        $newValue = $values->$customInputName;
                        if ($newValue->getError() == UPLOAD_ERR_OK) {
                            $path = $this->generatePath($newValue);
                            $this->filesService->save($newValue, $path);
                            $customInputValue->setValue($path);
                        }
                    }

                    $customInputValue->setUser($this->user);
                    $customInputValue->setInput($customInput);
                    $this->customInputValueRepository->save($customInputValue);

                    if ($oldValue !== $newValue) {
                        $customInputValueChanged = true;
                    }
                }

                if (property_exists($values, 'arrival')) {
                    $this->user->setArrival($values->arrival);
                }

                if (property_exists($values, 'departure')) {
                    $this->user->setDeparture($values->departure);
                }
            }

            $this->user->setAbout($values->about);

            $this->user->setNote($values->privateNote);

            $this->userRepository->save($this->user);

            if ($customInputValueChanged) {
                $this->mailService->sendMailFromTemplate($this->user, '', Template::CUSTOM_INPUT_VALUE_CHANGED, [
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
     * Vygeneruje cestu souboru.
     */
    private function generatePath(FileUpload $file) : string
    {
        return CustomFile::PATH . '/' . Random::generate(5) . '/' . Strings::webalize($file->name, '.');
    }
}
