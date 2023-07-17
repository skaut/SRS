<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Commands\SetSettingArrayValue;
use App\Model\Settings\Commands\SetSettingBoolValue;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Queries\SettingArrayValueQuery;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use App\Services\IMailService;
use App\Services\QueryBus;
use App\Utils\Validators;
use Doctrine\Common\Collections\ArrayCollection;
use Nette;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;
use Nextras\FormsRendering\Renderers\Bs5FormRenderer;
use stdClass;
use Throwable;

use function array_map;
use function assert;
use function explode;
use function implode;
use function md5;
use function mt_rand;
use function substr;
use function trim;
use function uniqid;

/**
 * Formulář pro nastavení mailingu.
 */
class MailingFormFactory
{
    use Nette\SmartObject;

    public function __construct(
        private BaseFormFactory $baseFormFactory,
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private IMailService $mailService,
        private LinkGenerator $linkGenerator,
        private Validators $validators,
    ) {
    }

    /**
     * Vytvoří formulář.
     *
     * @throws Throwable
     */
    public function create(int $id): Form
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        assert($renderer instanceof Bs5FormRenderer);
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
            'seminarEmail' => $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_EMAIL)),
            'contactFormRecipients' => implode(', ', $this->queryBus->handle(new SettingArrayValueQuery(Settings::CONTACT_FORM_RECIPIENTS))),
            'contactFormGuestsAllowed' => $this->queryBus->handle(new SettingBoolValueQuery(Settings::CONTACT_FORM_GUESTS_ALLOWED)),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws Nette\Application\UI\InvalidLinkException
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values): void
    {
        if ($this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_EMAIL)) !== $values->seminarEmail) {
            $this->commandBus->handle(new SetSettingStringValue(Settings::SEMINAR_EMAIL_UNVERIFIED, $values->seminarEmail));

            $verificationCode = substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
            $this->commandBus->handle(new SetSettingStringValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE, $verificationCode));

            $link = $this->linkGenerator->link('Action:Mailing:verify', ['code' => $verificationCode]);

            $this->mailService->sendMailFromTemplate(
                null,
                new ArrayCollection([$values->seminarEmail]),
                Template::EMAIL_VERIFICATION,
                [
                    TemplateVariable::SEMINAR_NAME => $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME)),
                    TemplateVariable::EMAIL_VERIFICATION_LINK => $link,
                ],
            );
        }

        $contactFormRecipients = array_map(
            static fn (string $o) => trim($o),
            explode(',', $values->contactFormRecipients),
        );
        $this->commandBus->handle(new SetSettingArrayValue(Settings::CONTACT_FORM_RECIPIENTS, $contactFormRecipients));

        $this->commandBus->handle(new SetSettingBoolValue(Settings::CONTACT_FORM_GUESTS_ALLOWED, $values->contactFormGuestsAllowed));
    }

    /**
     * Ověří seznam e-mailů oddělených ','.
     */
    public function validateEmails(TextInput $field): bool
    {
        return $this->validators->validateEmails($field->getValue());
    }
}
