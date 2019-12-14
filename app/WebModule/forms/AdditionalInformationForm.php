<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\CustomInput\CustomFile;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
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
use App\Services\SettingsService;
use InvalidArgumentException;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Nettrine\ORM\EntityManagerDecorator;
use stdClass;
use Throwable;
use function array_key_exists;
use function array_slice;
use function array_values;
use function explode;

/**
 * Formulář pro zadání doplňujících informací.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
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
     * @var callable
     */
    public $onSave;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var EntityManagerDecorator */
    private $em;

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

    /** @var SettingsService */
    private $settingsService;


    public function __construct(
        BaseForm $baseFormFactory,
        EntityManagerDecorator $em,
        UserRepository $userRepository,
        CustomInputRepository $customInputRepository,
        ApplicationService $applicationService,
        CustomInputValueRepository $customInputValueRepository,
        FilesService $filesService,
        MailService $mailService,
        SettingsService $settingsService
    ) {
        parent::__construct();

        $this->baseFormFactory            = $baseFormFactory;
        $this->em                         = $em;
        $this->userRepository             = $userRepository;
        $this->customInputRepository      = $customInputRepository;
        $this->applicationService         = $applicationService;
        $this->customInputValueRepository = $customInputValueRepository;
        $this->filesService               = $filesService;
        $this->mailService                = $mailService;
        $this->settingsService            = $settingsService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/additional_information_form.latte');
        $this->template->render();
    }

    /**
     * Vytvoří formulář.
     * @throws SettingsException
     * @throws Throwable
     */
    public function createComponentForm() : Form
    {
        $this->user                = $this->userRepository->findById($this->presenter->user->getId());
        $isAllowedEditCustomInputs = $this->applicationService->isAllowedEditCustomInputs();

        $form = $this->baseFormFactory->create();

        foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
            $customInputValue = $this->user->getCustomInputValue($customInput);

            switch ($customInput->getType()) {
                case CustomInput::TEXT:
                    $custom = $form->addText('custom' . $customInput->getId(), $customInput->getName())
                        ->setDisabled(! $isAllowedEditCustomInputs);
                    if ($customInputValue) {
                        $custom->setDefaultValue($customInputValue->getValue());
                    }
                    break;

                case CustomInput::CHECKBOX:
                    $custom = $form->addCheckbox('custom' . $customInput->getId(), $customInput->getName())
                        ->setDisabled(! $isAllowedEditCustomInputs);
                    if ($customInputValue) {
                        $custom->setDefaultValue($customInputValue->getValue());
                    }
                    break;

                case CustomInput::SELECT:
                    $custom = $form->addSelect('custom' . $customInput->getId(), $customInput->getName(), $customInput->getSelectOptions())
                        ->setDisabled(! $isAllowedEditCustomInputs);
                    if ($customInputValue) {
                        $custom->setDefaultValue($customInputValue->getValue());
                    }
                    break;

                case CustomInput::FILE:
                    $custom = $form->addUpload('custom' . $customInput->getId(), $customInput->getName())
                        ->setDisabled(! $isAllowedEditCustomInputs);
                    if ($customInputValue && $customInputValue->getValue()) {
                        $custom->setAttribute('data-current-file-link', $customInputValue->getValue())
                            ->setAttribute('data-current-file-name', array_values(array_slice(explode('/', $customInputValue->getValue()), -1))[0]);
                    }
                    break;

                default:
                    throw new InvalidArgumentException();
            }

            if ($customInput->isMandatory() && $customInput->getType() !== CustomInput::FILE) {
                $custom->addRule(Form::FILLED, 'web.profile.custom_input_empty');
            }
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
            'departure' => $this->user->getDeparture(),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        $this->em->transactional(function () use ($values) : void {
            $customInputValueChanged = false;

            if ($this->applicationService->isAllowedEditCustomInputs()) {
                foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
                    $customInputValue = $this->user->getCustomInputValue($customInput);

                    $oldValue = $customInputValue ? $customInputValue->getValue() : null;

                    switch ($customInput->getType()) {
                        case CustomInput::TEXT:
                            $customInputValue = $customInputValue ?: new CustomTextValue();
                            $customInputValue->setValue($values->custom' . $customInput->getId()]);
                            break;

                        case CustomInput::CHECKBOX:
                            $customInputValue = $customInputValue ?: new CustomCheckboxValue();
                            $customInputValue->setValue($values->custom' . $customInput->getId()]);
                            break;

                        case CustomInput::SELECT:
                            $customInputValue = $customInputValue ?: new CustomSelectValue();
                            $customInputValue->setValue($values->custom' . $customInput->getId()]);
                            break;

                        case CustomInput::FILE:
                            $customInputValue = $customInputValue ?: new CustomFileValue();
                            $file             = $values->custom' . $customInput->getId()];
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
            }

            $this->user->setAbout($values->about);

            if (array_key_exists('arrival', $values)) {
                $this->user->setArrival($values->arrival);
            }
            if (array_key_exists('departure', $values)) {
                $this->user->setDeparture($values->departure);
            }

            $this->userRepository->save($this->user);

            if (! $customInputValueChanged) {
                return;
            }

            $this->mailService->sendMailFromTemplate($this->user, '', Template::CUSTOM_INPUT_VALUE_CHANGED, [
                TemplateVariable::SEMINAR_NAME => $this->settingsService->getValue(Settings::SEMINAR_NAME),
                TemplateVariable::USER => $this->user->getDisplayName(),
            ]);
        });

        $this->onSave($this);
    }

    /**
     * Vygeneruje cestu souboru.
     */
    private function generatePath(FileUpload $file) : string
    {
        return CustomFile::PATH . '/' . Random::generate(5) . '/' . Strings::webalize($file->name, '.');
    }
}
