<?php

namespace App\WebModule\Forms;

use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\CustomInput\CustomFile;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\Settings;
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
use App\Services\MailService;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use Nette\Utils\Random;
use Nette\Utils\Strings;


/**
 * Formulář pro zadání doplňujících informací.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class AdditionalInformationForm extends UI\Control
{
    /**
     * Přihlášený uživatel.
     * @var User
     */
    private $user;

    /**
     * Událost při uložení formuláře.
     */
    public $onSave;

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

    /** @var FilesService */
    private $filesService;

    /** @var MailService */
    private $mailService;

    /** @var SettingsRepository */
    private $settingsRepository;


    /**
     * AdditionalInformationForm constructor.
     * @param BaseForm $baseFormFactory
     * @param UserRepository $userRepository
     * @param CustomInputRepository $customInputRepository
     * @param ApplicationService $applicationService
     * @param CustomInputValueRepository $customInputValueRepository
     */
    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository,
                                CustomInputRepository $customInputRepository, ApplicationService $applicationService,
                                CustomInputValueRepository $customInputValueRepository, FilesService $filesService,
                                MailService $mailService, SettingsRepository $settingsRepository)
    {
        parent::__construct();

        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->customInputRepository = $customInputRepository;
        $this->applicationService = $applicationService;
        $this->customInputValueRepository = $customInputValueRepository;
        $this->filesService = $filesService;
        $this->mailService = $mailService;
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/additional_information_form.latte');
        $this->template->render();
    }

    /**
     * Vytvoří formulář.
     * @return Form
     * @throws \App\Model\Settings\SettingsException
     */
    public function createComponentForm()
    {
        $this->user = $this->userRepository->findById($this->presenter->user->getId());

        $form = $this->baseFormFactory->create();

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
                    if ($customInputValue && $customInputValue->getValue())
                        $custom->setAttribute('data-current-file', $customInputValue->getValue());
                    break;
            }

            if ($customInput->isMandatory() && $customInput->getType() != CustomInput::FILE)
                $custom->addRule(Form::FILLED, 'web.profile.custom_input_empty');

            if (!$this->applicationService->isAllowedEditCustomInputs())
                $custom->setDisabled();
        }

        $form->addTextArea('about', 'web.profile.about_me');

        if ($this->user->hasDisplayArrivalDepartureRole()) {
            $form->addDateTimePicker('arrival', 'web.profile.arrival');
            $form->addDateTimePicker('departure', 'web.profile.departure');
        }

        $form->addSubmit('submit', 'web.profile.update_additional_information');

        $form->setDefaults([
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
     * @throws \Throwable
     */
    public function processForm(Form $form, \stdClass $values)
    {
        $this->userRepository->getEntityManager()->transactional(function ($em) use ($values) {
            $customInputValueChanged = FALSE;

            if ($this->applicationService->isAllowedEditCustomInputs()) {
                foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
                    $customInputValue = $this->user->getCustomInputValue($customInput);

                    $oldValue = $customInputValue ? $customInputValue->getValue() : NULL;

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
                                $path = $this->generatePath($file);
                                $this->filesService->save($file, $path);
                                $customInputValue->setValue($path);
                            }
                            break;
                    }

                    $customInputValue->setUser($this->user);
                    $customInputValue->setInput($customInput);
                    $this->customInputValueRepository->save($customInputValue);

                    if ($oldValue !== $customInputValue->getValue())
                        $customInputValueChanged = TRUE;
                }
            }

            $this->user->setAbout($values['about']);

            if (array_key_exists('arrival', $values))
                $this->user->setArrival($values['arrival']);
            if (array_key_exists('departure', $values))
                $this->user->setDeparture($values['departure']);

            $this->userRepository->save($this->user);

            if ($customInputValueChanged) {
                $this->mailService->sendMailFromTemplate($this->user, '', Template::CUSTOM_INPUT_VALUE_CHANGED, [
                    TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
                    TemplateVariable::USER => $this->user->getDisplayName()
                ]);
            }
        });

        $this->onSave($this);
    }

    /**
     * Vygeneruje cestu souboru.
     * @param $file
     * @return string
     */
    private function generatePath($file): string
    {
        return CustomFile::PATH . '/' . Random::generate(5) . '/' . Strings::webalize($file->name, '.');
    }
}
