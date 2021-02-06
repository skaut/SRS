<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

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
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\ApplicationService;
use App\Services\FilesService;
use App\Services\IMailService;
use App\Services\ISettingsService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Nette;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nextras\FormComponents\Controls\DateControl;
use Nextras\FormComponents\Controls\DateTimeControl;
use stdClass;
use Throwable;

use function array_key_exists;
use function array_slice;
use function array_values;
use function assert;
use function explode;

use const UPLOAD_ERR_OK;

/**
 * Formulář pro zadání doplňujících informací.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class AdditionalInformationFormFactory
{
    use Nette\SmartObject;

    /**
     * Přihlášený uživatel.
     */
    private ?User $user = null;

    private BaseFormFactory $baseFormFactory;

    private EntityManagerInterface $em;

    private UserRepository $userRepository;

    private CustomInputRepository $customInputRepository;

    private ApplicationService $applicationService;

    private CustomInputValueRepository $customInputValueRepository;

    private FilesService $filesService;

    private IMailService $mailService;

    private ISettingsService $settingsService;

    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        CustomInputRepository $customInputRepository,
        ApplicationService $applicationService,
        CustomInputValueRepository $customInputValueRepository,
        FilesService $filesService,
        IMailService $mailService,
        ISettingsService $settingsService
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
     * Vytvoří formulář.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function create(int $userId): Form
    {
        $this->user = $this->userRepository->findById($userId);
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
                    $custom->setHtmlAttribute('data-show-preview', 'true')
                        ->setHtmlAttribute('data-initial-preview-file-type', 'other');

                    $customInputValue = $this->user->getCustomInputValue($customInput);
                    if ($customInputValue) {
                        assert($customInputValue instanceof CustomFileValue);
                        $custom->setHtmlAttribute('data-initial-preview', '["' . $customInputValue->getValue() . '"]')
                            ->setHtmlAttribute('data-initial-preview-download-url', $customInputValue->getValue());
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
    public function processForm(Form $form, stdClass $values): void
    {
        $this->em->transactional(function () use ($values): void {
            $customInputValueChanged = false;

            if ($this->applicationService->isAllowedEditCustomInputs()) {
                foreach ($this->customInputRepository->findByRolesOrderedByPosition($this->user->getRoles()) as $customInput) {
                    $customInputId    = 'custom' . $customInput->getId();
                    $customInputValue = $this->user->getCustomInputValue($customInput);
                    $oldValue         = null;
                    $newValue         = $values->$customInputId;

                    if ($customInput instanceof CustomText) {
                        /** @var CustomTextValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomTextValue($customInput, $this->user);
                        $oldValue         = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomCheckbox) {
                        /** @var CustomCheckboxValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomCheckboxValue($customInput, $this->user);
                        $oldValue         = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomSelect) {
                        /** @var CustomSelectValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomSelectValue($customInput, $this->user);
                        $oldValue         = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomMultiSelect) {
                        /** @var CustomMultiSelectValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomMultiSelectValue($customInput, $this->user);
                        $oldValue         = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomFile) {
                        /** @var CustomFileValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomFileValue($customInput, $this->user);
                        $oldValue         = $customInputValue->getValue();
                        /** @var FileUpload $newValue */
                        $newValue = $values->$customInputId;
                        if ($newValue->getError() == UPLOAD_ERR_OK) {
                            $path = $this->filesService->save($newValue, CustomFile::PATH, true, $newValue->name);
                            $customInputValue->setValue($path);
                        }
                    } elseif ($customInput instanceof CustomDate) {
                        /** @var CustomDateValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomDateValue($customInput, $this->user);
                        $oldValue         = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    } elseif ($customInput instanceof CustomDateTime) {
                        /** @var CustomDateTimeValue $customInputValue */
                        $customInputValue = $customInputValue ?: new CustomDateTimeValue($customInput, $this->user);
                        $oldValue         = $customInputValue->getValue();
                        $customInputValue->setValue($newValue);
                    }

                    $this->customInputValueRepository->save($customInputValue);

                    if ($oldValue !== $newValue) {
                        $customInputValueChanged = true;
                    }
                }
            }

            $this->user->setAbout($values->about);

            $this->userRepository->save($this->user);

            if ($customInputValueChanged) {
                assert($this->user instanceof User);
                $this->mailService->sendMailFromTemplate(new ArrayCollection([$this->user]), null, Template::CUSTOM_INPUT_VALUE_CHANGED, [
                    TemplateVariable::SEMINAR_NAME => $this->settingsService->getValue(Settings::SEMINAR_NAME),
                    TemplateVariable::USER => $this->user->getDisplayName(),
                ]);
            }
        });
    }
}
