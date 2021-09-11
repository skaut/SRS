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
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\ApplicationService;
use App\Services\FilesService;
use App\Services\IMailService;
use App\Services\QueryBus;
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
use function assert;
use function basename;
use function json_encode;

use const UPLOAD_ERR_OK;

/**
 * Formulář pro zadání doplňujících informací.
 */
class AdditionalInformationFormFactory
{
    use Nette\SmartObject;

    /**
     * Přihlášený uživatel.
     */
    private ?User $user = null;

    private BaseFormFactory $baseFormFactory;

    private QueryBus $queryBus;

    private EntityManagerInterface $em;

    private UserRepository $userRepository;

    private CustomInputRepository $customInputRepository;

    private ApplicationService $applicationService;

    private CustomInputValueRepository $customInputValueRepository;

    private FilesService $filesService;

    private IMailService $mailService;

    public function __construct(
        BaseFormFactory $baseFormFactory,
        QueryBus $queryBus,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        CustomInputRepository $customInputRepository,
        ApplicationService $applicationService,
        CustomInputValueRepository $customInputValueRepository,
        FilesService $filesService,
        IMailService $mailService
    ) {
        $this->baseFormFactory            = $baseFormFactory;
        $this->queryBus                   = $queryBus;
        $this->em                         = $em;
        $this->userRepository             = $userRepository;
        $this->customInputRepository      = $customInputRepository;
        $this->applicationService         = $applicationService;
        $this->customInputValueRepository = $customInputValueRepository;
        $this->filesService               = $filesService;
        $this->mailService                = $mailService;
    }

    /**
     * Vytvoří formulář.
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function create(int $userId): Form
    {
        $this->user                = $this->userRepository->findById($userId);
        $isAllowedEditCustomInputs = $this->applicationService->isAllowedEditCustomInputs();

        $form = $this->baseFormFactory->create();

        foreach ($this->customInputRepository->findByRolesOrderedByPosition($this->user->getRoles()) as $customInput) {
            $customInputId = 'custom' . $customInput->getId();
            $custom        = null;

            switch (true) {
                case $customInput instanceof CustomText:
                    $custom           = $form->addText($customInputId, $customInput->getName());
                    $customInputValue = $this->user->getCustomInputValue($customInput);
                    if ($customInputValue) {
                        assert($customInputValue instanceof CustomTextValue);
                        $custom->setDefaultValue($customInputValue->getValue());
                    }

                    break;

                case $customInput instanceof CustomCheckbox:
                    $custom           = $form->addCheckbox($customInputId, $customInput->getName());
                    $customInputValue = $this->user->getCustomInputValue($customInput);
                    if ($customInputValue) {
                        assert($customInputValue instanceof CustomCheckboxValue);
                        $custom->setDefaultValue($customInputValue->getValue());
                    }

                    break;

                case $customInput instanceof CustomSelect:
                    $selectOptions    = $customInput->getSelectOptions();
                    $custom           = $form->addSelect($customInputId, $customInput->getName(), $selectOptions);
                    $customInputValue = $this->user->getCustomInputValue($customInput);
                    if ($customInputValue) {
                        assert($customInputValue instanceof CustomSelectValue);
                        if (array_key_exists($customInputValue->getValue(), $selectOptions)) {
                            $custom->setDefaultValue($customInputValue->getValue());
                        }
                    }

                    break;

                case $customInput instanceof CustomMultiSelect:
                    $custom           = $form->addMultiSelect($customInputId, $customInput->getName(), $customInput->getSelectOptions());
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
                        $file = $customInputValue->getValue();
                        $custom->setHtmlAttribute('data-initial-preview', json_encode([$file]))
                            ->setHtmlAttribute('data-initial-preview-file-type', 'other')
                            ->setHtmlAttribute('data-initial-preview-config', json_encode([
                                [
                                    'caption' => basename($file),
                                    'downloadUrl' => $file,
                                ],
                            ]));
                    }

                    break;

                case $customInput instanceof CustomDate:
                    $custom           = new DateControl($customInput->getName());
                    $customInputValue = $this->user->getCustomInputValue($customInput);
                    if ($customInputValue) {
                        assert($customInputValue instanceof CustomDateValue);
                        $custom->setDefaultValue($customInputValue->getValue());
                    }

                    $form->addComponent($custom, $customInputId);
                    break;

                case $customInput instanceof CustomDateTime:
                    $custom           = new DateTimeControl($customInput->getName());
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
                        $newValue = $values->$customInputId;
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
}
