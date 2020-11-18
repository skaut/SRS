<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Services\MailService;
use App\Services\SettingsService;
use App\Utils\Validators;
use Doctrine\Common\Collections\ArrayCollection;
use Nette;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;
use Nextras\FormsRendering\Renderers\Bs3FormRenderer;
use stdClass;
use Throwable;
use Ublaboo\Mailing\Exception\MailingMailCreationException;
use function array_map;
use function explode;
use function implode;
use function md5;
use function mt_rand;
use function substr;
use function trim;
use function uniqid;

/**
 * Formulář pro nastavení mailingu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailingFormFactory
{
    use Nette\SmartObject;

    private BaseFormFactory $baseFormFactory;

    private SettingsService $settingsService;

    private MailService $mailService;

    private LinkGenerator $linkGenerator;

    private Validators $validators;

    public function __construct(
        BaseFormFactory $baseForm,
        SettingsService $settingsService,
        MailService $mailService,
        LinkGenerator $linkGenerator,
        Validators $validators
    ) {
        $this->baseFormFactory = $baseForm;
        $this->settingsService = $settingsService;
        $this->mailService     = $mailService;
        $this->linkGenerator   = $linkGenerator;
        $this->validators      = $validators;
    }

    /**
     * Vytvoří formulář.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function create(int $id) : Form
    {
        $form = $this->baseFormFactory->create();

        /** @var Bs3FormRenderer $renderer */
        $renderer                                   = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-5 col-form-label"';

        $form->addText('seminarEmail', 'admin.configuration.mailing_email')
            ->addRule(Form::FILLED, 'admin.configuration.mailing_email_empty')
            ->addRule(Form::EMAIL, 'admin.configuration.mailing_email_format');

        $form->addText('contactFormRecipients', 'admin.configuration.mailing_contact_form_recipients')
            ->addRule(Form::FILLED, 'admin.configuration.mailing_contact_form_recipients_empty')
            ->addRule([$this, 'validateEmails'], 'admin.configuration.mailing_contact_form_recipients_format');

        $form->addCheckbox('contactFormGuestsAllowed', 'admin.configuration.mailing_contact_form_guests_allowed');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'seminarEmail' => $this->settingsService->getValue(Settings::SEMINAR_EMAIL),
            'contactFormRecipients' => implode(', ', $this->settingsService->getArrayValue(Settings::CONTACT_FORM_RECIPIENTS)),
            'contactFormGuestsAllowed' => $this->settingsService->getBoolValue(Settings::CONTACT_FORM_GUESTS_ALLOWED),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws Nette\Application\UI\InvalidLinkException
     * @throws SettingsException
     * @throws Throwable
     * @throws MailingMailCreationException
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        if ($this->settingsService->getValue(Settings::SEMINAR_EMAIL) !== $values->seminarEmail) {
            $this->settingsService->setValue(Settings::SEMINAR_EMAIL_UNVERIFIED, $values->seminarEmail);

            $verificationCode = substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
            $this->settingsService->setValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE, $verificationCode);

            $link = $this->linkGenerator->link('Action:Mailing:verify', ['code' => $verificationCode]);

            $this->mailService->sendMailFromTemplate(
                null,
                new ArrayCollection([$values->seminarEmail]),
                Template::EMAIL_VERIFICATION,
                [
                    TemplateVariable::SEMINAR_NAME => $this->settingsService->getValue(Settings::SEMINAR_NAME),
                    TemplateVariable::EMAIL_VERIFICATION_LINK => $link,
                ]
            );
        }

        $contactFormRecipients = array_map(
            static function (string $o) {
                return trim($o);
            },
            explode(',', $values->contactFormRecipients)
        );
        $this->settingsService->setArrayValue(Settings::CONTACT_FORM_RECIPIENTS, $contactFormRecipients);

        $this->settingsService->setBoolValue(Settings::CONTACT_FORM_GUESTS_ALLOWED, $values->contactFormGuestsAllowed);
    }

    /**
     * Ověří seznam e-mailů oddělených ','.
     */
    public function validateEmails(TextInput $field) : bool
    {
        return $this->validators->validateEmails($field->getValue());
    }
}
