<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

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
use App\Model\Settings\SettingsException;
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
use App\Services\ApplicationService;
use App\Services\FilesService;
use App\Services\MailService;
use App\Services\SettingsService;
use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Nettrine\ORM\EntityManagerDecorator;
use Nextras\FormComponents\Controls\DateControl;
use Nextras\FormComponents\Controls\DateTimeControl;
use stdClass;
use Throwable;
use function array_key_exists;
use function array_slice;
use function array_values;
use function explode;
use const UPLOAD_ERR_OK;

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
     */
    private ?User $user = null;

    /**
     * Událost při uložení formuláře.
     *
     * @var callable[]
     */
    public array $onSave = [];

    private BaseFormFactory $baseFormFactory;

    private EntityManagerDecorator $em;

    private UserRepository $userRepository;

    private CustomInputRepository $customInputRepository;

    private ApplicationService $applicationService;

    private CustomInputValueRepository $customInputValueRepository;

    private FilesService $filesService;

    private MailService $mailService;

    private SettingsService $settingsService;

    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManagerDecorator $em,
        UserRepository $userRepository,
        CustomInputRepository $customInputRepository,
        ApplicationService $applicationService,
        CustomInputValueRepository $customInputValueRepository,
        FilesService $filesService,
        MailService $mailService,
        SettingsService $settingsService
    ) {
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
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function createComponentForm() : Form
    {
        $this->user                = $this->userRepository->findById($this->presenter->user->getId());
        $isAllowedEditCustomInputs = $this->applicationService->isAllowedEditCustomInputs();

        $form = $this->baseFormFactory->create();

        foreach ($this->customInputRepository->findByRolesOrderedByPosition($this->user->getRoles()) as $customInput) {
            $customInputId = 'custom' . $customInput->getId();
            $custom        = null;

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

                    /** @var ?CustomFileValue $customInputValue */
                    $customInputValue = $this->user->getCustomInputValue($customInput);
                    if ($customInputValue && $customInputValue->getValue()) {
                        $custom->setHtmlAttribute('data-current-file-link', $customInputValue->getValue())
                            ->setHtmlAttribute('data-current-file-name', array_values(array_slice(explode('/', $customInputValue->getValue()), -1))[0]);
                    }

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

            $custom->setDisabled(! $isAllowedEditCustomInputs);

            if ($customInput->isMandatory()) {
                $custom->addRule(Form::FILLED, 'web.profile.custom_input_empty');
            }
        }

        $form->addTextArea('about', 'web.profile.about_me');

        $form->addSubmit('submit', 'web.profile.update_additional_information');

        $form->setDefaults([
            'about' => $this->user->getAbout(),
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
        $this->em->transactional(function () use ($values) : void {
            $customInputValueChanged = false;

            if ($this->applicationService->isAllowedEditCustomInputs()) {
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

            $this->userRepository->save($this->user);

            if ($customInputValueChanged) {
                $this->mailService->sendMailFromTemplate(new ArrayCollection([$this->user]), null, Template::CUSTOM_INPUT_VALUE_CHANGED, [
                    TemplateVariable::SEMINAR_NAME => $this->settingsService->getValue(Settings::SEMINAR_NAME),
                    TemplateVariable::USER => $this->user->getDisplayName(),
                ]);
            }
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
