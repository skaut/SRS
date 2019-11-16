<?php

declare(strict_types=1);

namespace App\AdminModule\Forms;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\EntityManagerDecorator;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\CustomInput\CustomFile;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\CustomInput\CustomSelect;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsFacade;
use App\Model\User\CustomInputValue\CustomCheckboxValue;
use App\Model\User\CustomInputValue\CustomFileValue;
use App\Model\User\CustomInputValue\CustomInputValueRepository;
use App\Model\User\CustomInputValue\CustomSelectValue;
use App\Model\User\CustomInputValue\CustomTextValue;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\FilesService;
use App\Services\MailService;
use App\Utils\Validators;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Http\FileUpload;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use function array_key_exists;

/**
 * Formulář pro úpravu podrobností o účasti uživatele na semináři.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class EditUserSeminarForm
{
    use Nette\SmartObject;

    /**
     * Upravovaný uživatel.
     * @var User
     */
    private $user;

    /** @var BaseForm */
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

    /** @var SettingsFacade */
    private $settingsFacade;


    public function __construct(
        BaseForm $baseFormFactory,
        EntityManagerDecorator $em,
        UserRepository $userRepository,
        CustomInputRepository $customInputRepository,
        CustomInputValueRepository $customInputValueRepository,
        RoleRepository $roleRepository,
        ApplicationService $applicationService,
        Validators $validators,
        FilesService $filesService,
        MailService $mailService,
        SettingsFacade $settingsFacade
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
        $this->settingsFacade             = $settingsFacade;
    }

    /**
     * Vytvoří formulář.
     */
    public function create(int $id) : Form
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addMultiSelect(
            'roles',
            'admin.users.users_roles',
            $this->roleRepository->getRolesWithoutRolesOptionsWithCapacity([Role::GUEST, Role::UNAPPROVED])
        )
            ->addRule(Form::FILLED, 'admin.users.users_edit_roles_empty')
            ->addRule([$this, 'validateRolesNonregistered'], 'admin.users.users_edit_roles_nonregistered')
            ->addRule([$this, 'validateRolesCapacities'], 'admin.users.users_edit_roles_occupied');

        $form->addCheckbox('approved', 'admin.users.users_approved_form');

        $form->addCheckbox('attended', 'admin.users.users_attended_form');

        if ($this->user->hasDisplayArrivalDepartureRole()) {
            $form->addDateTimePicker('arrival', 'admin.users.users_arrival');
            $form->addDateTimePicker('departure', 'admin.users.users_departure');
        }

        foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
            $customInputValue = $this->user->getCustomInputValue($customInput);

            switch ($customInput->getType()) {
                case CustomInput::TEXT:
                    $custom = $form->addText('custom' . $customInput->getId(), $customInput->getName());
                    if ($customInputValue) {
                        /** @var CustomTextValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomTextValue();
                        $custom->setDefaultValue($customInputValue->getValue());
                    }
                    break;

                case CustomInput::CHECKBOX:
                    $custom = $form->addCheckbox('custom' . $customInput->getId(), $customInput->getName());
                    if ($customInputValue) {
                        /** @var CustomCheckboxValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomCheckboxValue();
                        $custom->setDefaultValue($customInputValue->getValue());
                    }
                    break;

                case CustomInput::SELECT:
                    /** @var CustomSelect $customInput */
                    $customInput = $customInput ?: new CustomSelect();
                    $custom      = $form->addSelect('custom' . $customInput->getId(), $customInput->getName(), $customInput->getSelectOptions());
                    if ($customInputValue) {
                        /** @var CustomSelectValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomSelectValue();
                        $custom->setDefaultValue($customInputValue->getValue());
                    }
                    break;

                case CustomInput::FILE:
                    $form->addUpload('custom' . $customInput->getId(), $customInput->getName());
                    break;
            }
        }

        $form->addTextArea('about', 'admin.users.users_about_me');

        $form->addTextArea('privateNote', 'admin.users.users_private_note');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setAttribute('class', 'btn btn-warning');

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
     * @throws \Throwable
     */
    public function processForm(Form $form, \stdClass $values) : void
    {
        if ($form['cancel']->isSubmittedBy()) {
            return;
        }

        $loggedUser = $this->userRepository->findById($form->getPresenter()->user->id);

        $this->em->transactional(function ($em) use ($values, $loggedUser) : void {
            $selectedRoles = $this->roleRepository->findRolesByIds($values['roles']);
            $this->applicationService->updateRoles($this->user, $selectedRoles, $loggedUser);

            $this->user->setApproved($values['approved']);
            $this->user->setAttended($values['attended']);

            $customInputValueChanged = false;

            foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
                /** @var CustomTextValue $customInputValue */
                $customInputValue = $this->user->getCustomInputValue($customInput);

                $oldValue = $customInputValue ? $customInputValue->getValue() : null;

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

                if ($oldValue === $customInputValue->getValue()) {
                    continue;
                }

                $customInputValueChanged = true;
            }

            if (array_key_exists('arrival', $values)) {
                $this->user->setArrival($values['arrival']);
            }

            if (array_key_exists('departure', $values)) {
                $this->user->setDeparture($values['departure']);
            }

            $this->user->setAbout($values['about']);

            $this->user->setNote($values['privateNote']);

            $this->userRepository->save($this->user);

            if (! $customInputValueChanged) {
                return;
            }

            $this->mailService->sendMailFromTemplate($this->user, '', Template::CUSTOM_INPUT_VALUE_CHANGED, [
                TemplateVariable::SEMINAR_NAME => $this->settingsFacade->getValue(Settings::SEMINAR_NAME),
                TemplateVariable::USER => $this->user->getDisplayName(),
            ]);
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
