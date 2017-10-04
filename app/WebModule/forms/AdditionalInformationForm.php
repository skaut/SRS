<?php

namespace App\WebModule\Forms;

use App\Model\Enums\ApplicationState;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\User\CustomInputValue\CustomCheckboxValue;
use App\Model\User\CustomInputValue\CustomInputValueRepository;
use App\Model\User\CustomInputValue\CustomSelectValue;
use App\Model\User\CustomInputValue\CustomTextValue;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro zadání doplňujících informací.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class AdditionalInformationForm extends Nette\Object
{
    /**
     * Přihlášený uživatel.
     * @var User
     */
    private $user;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var CustomInputRepository */
    private $customInputRepository;

    /** @var ApplicationService */
    private $applicationService;

    /** @var CustomInputValueRepository */
    private $customInputValueRepository;


    /**
     * AdditionalInformationForm constructor.
     * @param BaseForm $baseFormFactory
     * @param UserRepository $userRepository
     * @param CustomInputRepository $customInputRepository
     * @param ApplicationService $applicationService
     */
    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository,
                                CustomInputRepository $customInputRepository, ApplicationService $applicationService,
                                CustomInputValueRepository $customInputValueRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->customInputRepository = $customInputRepository;
        $this->applicationService = $applicationService;
        $this->customInputValueRepository = $customInputValueRepository;
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

        foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
            $customInputValue = $this->user->getCustomInputValue($customInput);

            switch ($customInput->getType()) {
                case CustomInput::TEXT:
                    $custom = $form->addText('custom' . $customInput->getId(), $customInput->getName());
                    break;

                case CustomInput::CHECKBOX:
                    $custom = $form->addCheckbox('custom' . $customInput->getId(), $customInput->getName());
                    break;

                case CustomInput::SELECT:
                    $custom = $form->addSelect('custom' . $customInput->getId(), $customInput->getName(), $customInput->prepareSelectOptions());
                    break;
            }

            if ($customInput->isMandatory())
                $custom->addRule(Form::FILLED, 'web.profile.custom_input_empty');

            if (!$this->applicationService->isAllowedEditCustomInputs())
                $custom->setDisabled();

            if ($customInputValue)
                $custom->setDefaultValue($customInputValue->getValue());
        }

        $form->addTextArea('about', 'web.profile.about_me');

        if ($this->user->hasDisplayArrivalDepartureRole()) {
            $form->addDateTimePicker('arrival', 'web.profile.arrival');
            $form->addDateTimePicker('departure', 'web.profile.departure');
        }

        $form->addSubmit('submit', 'web.profile.update_additional_information');

        $form->setDefaults([
            'id' => $id,
            'about' => $this->user->getAbout(),
            'arrival' => $this->user->getArrival(),
            'departure' => $this->user->getDeparture()
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
        if ($this->applicationService->isAllowedEditCustomInputs()) {
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
                    case CustomInput::SELECT:
                        $customInputValue = new CustomSelectValue();
                        break;
                }
                $customInputValue->setValue($values['custom' . $customInput->getId()]);
                $customInputValue->setUser($this->user);
                $customInputValue->setInput($customInput);
                $this->customInputValueRepository->save($customInputValue);
            }
        }

        $this->user->setAbout($values['about']);

        if (array_key_exists('arrival', $values))
            $this->user->setArrival($values['arrival']);
        if (array_key_exists('departure', $values))
            $this->user->setDeparture($values['departure']);

        $this->userRepository->save($this->user);
    }
}
