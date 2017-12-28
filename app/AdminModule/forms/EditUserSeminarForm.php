<?php

namespace App\AdminModule\Forms;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\CustomInput\CustomFile;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\CustomInput\CustomText;
use App\Model\Settings\SettingsRepository;
use App\Model\User\CustomInputValue\CustomCheckboxValue;
use App\Model\User\CustomInputValue\CustomInputValueRepository;
use App\Model\User\CustomInputValue\CustomSelectValue;
use App\Model\User\CustomInputValue\CustomFileValue;
use App\Model\User\CustomInputValue\CustomTextValue;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\FilesService;
use App\Services\ProgramService;
use App\Utils\Validators;
use Nette;
use Nette\Application\UI\Form;
use PhpCollection\Set;
use phpDocumentor\Reflection\File;


/**
 * Formulář pro úpravu podrobností o účasti uživatele na semináři.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class EditUserSeminarForm extends Nette\Object
{
    /**
     * Upravovaný uživatel.
     * @var User
     */
    private $user;

    /** @var BaseForm */
    private $baseFormFactory;

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


    /**
     * EditUserSeminarForm constructor.
     * @param BaseForm $baseFormFactory
     * @param UserRepository $userRepository
     * @param CustomInputRepository $customInputRepository
     * @param CustomInputValueRepository $customInputValueRepository
     * @param RoleRepository $roleRepository
     * @param ApplicationService $applicationService
     * @param Validators $validators
     * @param FilesService $filesService
     */
    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository,
                                CustomInputRepository $customInputRepository,
                                CustomInputValueRepository $customInputValueRepository,
                                RoleRepository $roleRepository, ApplicationService $applicationService,
                                Validators $validators, FilesService $filesService)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->customInputRepository = $customInputRepository;
        $this->customInputValueRepository = $customInputValueRepository;
        $this->roleRepository = $roleRepository;
        $this->applicationService = $applicationService;
        $this->validators = $validators;
        $this->filesService = $filesService;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @return Form
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function create($id)
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addMultiSelect('roles', 'admin.users.users_roles',
            $this->roleRepository->getRolesWithoutRolesOptionsWithCapacity([Role::GUEST, Role::UNAPPROVED]))
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
                    if ($customInputValue)
                        $custom->setDefaultValue($customInputValue->getValue());
                    break;

                case CustomInput::CHECKBOX:
                    $custom = $form->addCheckbox('custom' . $customInput->getId(), $customInput->getName());
                    if ($customInputValue)
                        $custom->setDefaultValue($customInputValue->getValue());
                    break;

                case CustomInput::SELECT:
                    $custom = $form->addSelect('custom' . $customInput->getId(), $customInput->getName(), $customInput->prepareSelectOptions());
                    if ($customInputValue)
                        $custom->setDefaultValue($customInputValue->getValue());
                    break;

                case CustomInput::FILE:
                    $custom = $form->addUpload('custom' . $customInput->getId(), $customInput->getName());
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
            'privateNote' => $this->user->getNote()
        ]);


        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     * @throws \Throwable
     */
    public function processForm(Form $form, \stdClass $values)
    {
        if(!$form['cancel']->isSubmittedBy()) {
            $loggedUser = $this->userRepository->findById($form->getPresenter()->user->id);

            $this->userRepository->getEntityManager()->transactional(function ($em) use ($values, $loggedUser) {
                $selectedRoles = $this->roleRepository->findRolesByIds($values['roles']);
                $this->applicationService->updateRoles($this->user, $selectedRoles,  $loggedUser);

                $this->user->setApproved($values['approved']);
                $this->user->setAttended($values['attended']);

                foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
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
                            $file = $values['custom' . $customInput->getId()];
                            if ($file->size > 0) {
                                $path = $this->generatePath($file, $this->user, $customInput);
                                $this->filesService->save($file, $path);
                                $customInputValue->setValue($path);
                            }
                            break;
                    }

                    $customInputValue->setUser($this->user);
                    $customInputValue->setInput($customInput);
                    $this->customInputValueRepository->save($customInputValue);
                }

                if (array_key_exists('arrival', $values))
                    $this->user->setArrival($values['arrival']);

                if (array_key_exists('departure', $values))
                    $this->user->setDeparture($values['departure']);

                $this->user->setAbout($values['about']);

                $this->user->setNote($values['privateNote']);

                $this->userRepository->save($this->user);
            });
        }
    }

    /**
     * Ověří, že není vybrána role "Neregistrovaný".
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateRolesNonregistered($field, $args)
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        return $this->validators->validateRolesNonregistered($selectedRoles, $this->user);
    }

    /**
     * Ověří kapacitu rolí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateRolesCapacities($field, $args)
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        return $this->validators->validateRolesCapacities($selectedRoles, $this->user);
    }

    /**
     * Vygeneruje cestu souboru.
     * @param $file
     * @param User $user
     * @param CustomFile $customInput
     * @return string
     */
    private function generatePath($file, User $user, CustomFile $customInput): string
    {
        return CustomFile::PATH . '/' . $user->getId() . '-' . $customInput->getId() . '.' . pathinfo($file->name, PATHINFO_EXTENSION);
    }
}
