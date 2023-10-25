<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Commands\SetSettingArrayValue;
use App\Model\Settings\Commands\SetSettingBoolValue;
use App\Model\Settings\Queries\SettingArrayValueQuery;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use App\Services\QueryBus;
use App\Utils\Validators;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use stdClass;
use Throwable;

use function array_map;
use function assert;
use function explode;
use function implode;
use function trim;

/**
 * Formulář pro nastavení mailingu.
 */
class MailingFormFactory
{
    use Nette\SmartObject;

    public function __construct(
        private readonly BaseFormFactory $baseFormFactory,
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
        private readonly Validators $validators,
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
        assert($renderer instanceof Bs4FormRenderer);
        $renderer->wrappers['control']['container'] = 'div class="col-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-5 col-form-label"';

        $form->addText('contactFormRecipients', 'admin.configuration.mailing_contact_form_recipients')
            ->addRule(Form::FILLED, 'admin.configuration.mailing_contact_form_recipients_empty')
            ->addRule([$this, 'validateEmails'], 'admin.configuration.mailing_contact_form_recipients_format');

        $form->addCheckbox('contactFormGuestsAllowed', 'admin.configuration.mailing_contact_form_guests_allowed');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
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
