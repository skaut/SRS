<?php

namespace App\AdminModule\Forms;

use App\Model\Program\ProgramRepository;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\SettingsRepository;
use App\Model\User\CustomInputValue\CustomCheckboxValue;
use App\Model\User\CustomInputValue\CustomInputValueRepository;
use App\Model\User\CustomInputValue\CustomTextValue;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Nette;
use Nette\Application\UI\Form;


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

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var ProgramRepository */
    private $programRepository;


    /**
     * EditUserSeminarForm constructor.
     * @param BaseForm $baseFormFactory
     * @param UserRepository $userRepository
     * @param CustomInputRepository $customInputRepository
     * @param CustomInputValueRepository $customInputValueRepository
     * @param SettingsRepository $settingsRepository
     * @param ProgramRepository $programRepository
     */
    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository,
                                CustomInputRepository $customInputRepository,
                                CustomInputValueRepository $customInputValueRepository,
                                SettingsRepository $settingsRepository, ProgramRepository $programRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->customInputRepository = $customInputRepository;
        $this->customInputValueRepository = $customInputValueRepository;
        $this->settingsRepository = $settingsRepository;
        $this->programRepository = $programRepository;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @return Form
     */
    public function create($id)
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addCheckbox('approved', 'admin.users.users_approved_form');

        $form->addCheckbox('attended', 'admin.users.users_attended_form');

        if ($this->user->hasDisplayArrivalDepartureRole()) {
            $form->addDateTimePicker('arrival', 'admin.users.users_arrival');

            $form->addDateTimePicker('departure', 'admin.users.users_departure');
        }

        foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
            $customInputValue = $this->user->getCustomInputValue($customInput);

            switch ($customInput->getType()) {
                case 'text':
                    $customText = $form->addText('custom' . $customInput->getId(), $customInput->getName());
                    if ($customInputValue)
                        $customText->setDefaultValue($customInputValue->getValue());
                    break;

                case 'checkbox':
                    $customCheckbox = $form->addCheckbox('custom' . $customInput->getId(), $customInput->getName());
                    if ($customInputValue)
                        $customCheckbox->setDefaultValue($customInputValue->getValue());
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
     */
    public function processForm(Form $form, \stdClass $values)
    {
        if (!$form['cancel']->isSubmittedBy()) {
            $this->user->setApproved($values['approved']);
            $this->user->setAttended($values['attended']);

            foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
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
                }
                $customInputValue->setValue($values['custom' . $customInput->getId()]);
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

            $this->programRepository->updateUserPrograms($this->user);

            $this->userRepository->save($this->user);
        }
    }
}
