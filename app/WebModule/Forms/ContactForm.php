<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Settings;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\MailService;
use App\Services\SettingsService;
use Contributte\ReCaptcha\Forms\ReCaptchaField;
use Contributte\ReCaptcha\ReCaptchaProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use stdClass;
use function nl2br;

/**
 * Komponenta s formulářem pro kontaktaktní formulář.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ContactForm extends UI\Control
{
    /**
     * Přihlášený uživatel.
     */
    private ?User $user = null;

    /**
     * Událost při úspěšném odeslání formuláře.
     *
     * @var callable[]
     */
    public array $onSave = [];

    private BaseFormFactory $baseFormFactory;

    private UserRepository $userRepository;

    private ReCaptchaProvider $recaptchaProvider;

    private MailService $mailService;

    private SettingsService $settingsService;

    public function __construct(
        BaseFormFactory $baseFormFactory,
        UserRepository $userRepository,
        ReCaptchaProvider $recaptchaProvider,
        MailService $mailService,
        SettingsService $settingsService
    ) {
        $this->baseFormFactory   = $baseFormFactory;
        $this->userRepository    = $userRepository;
        $this->recaptchaProvider = $recaptchaProvider;
        $this->mailService       = $mailService;
        $this->settingsService   = $settingsService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/contact_form.latte');
        $this->template->render();
    }

    /**
     * Vytvoří formulář.
     */
    public function createComponentForm() : Form
    {
        $this->user = $this->userRepository->findById($this->presenter->user->getId());

        $form = $this->baseFormFactory->create();

        $nameText = $form->addText('name', 'web.contact_form_content.name')
            ->addRule(Form::FILLED, 'web.contact_form_content.name_empty');

        $emailText = $form->addText('email', 'web.contact_form_content.email')
            ->addRule(Form::FILLED, 'web.contact_form_content.email_empty')
            ->addRule(Form::EMAIL, 'web.contact_form_content.email_format');

        $form->addTextArea('message', 'web.contact_form_content.message')
            ->addRule(Form::FILLED, 'web.contact_form_content.message_empty');

        $form->addCheckbox('sendCopy', 'web.contact_form_content.send_copy');

        if ($this->user === null) {
            $field = new ReCaptchaField($this->recaptchaProvider);
            $field->addRule(Form::FILLED, 'web.contact_form_content.recaptcha_empty');
            $form->addComponent($field, 'recaptcha');
        }

        $form->addSubmit('submit', 'web.contact_form_content.send_message');

        if ($this->user !== null) {
            $nameText->setDisabled();
            $emailText->setDisabled();

            $form->setDefaults([
                'name' => $this->user->getDisplayName(),
                'email' => $this->user->getEmail(),
            ]);
        }

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        $recipientsUsers  = new ArrayCollection();
        $recipientsEmails = new ArrayCollection();

        $senderName  = null;
        $senderEmail = null;

        if ($this->user) {
            $senderName  = $this->user->getDisplayName();
            $senderEmail = $this->user->getEmail();
            if ($values->sendCopy) {
                $recipientsUsers->add($this->user);
            }
        } else {
            $senderName  = $values->name;
            $senderEmail = $values->email;
            if ($values->sendCopy) {
                $recipientsEmails->add($senderEmail);
            }
        }

        $recipients = $this->settingsService->getArrayValue(Settings::CONTACT_FORM_RECIPIENTS);
        foreach ($recipients as $recipient) {
            $recipientsEmails->add($recipient);
        }

        $this->mailService->sendMailFromTemplate(
            $recipientsUsers,
            $recipientsEmails,
            Template::CONTACT_FORM,
            [
                TemplateVariable::SEMINAR_NAME => $this->settingsService->getValue(Settings::SEMINAR_NAME),
                TemplateVariable::SENDER_NAME => $senderName,
                TemplateVariable::SENDER_EMAIL => $senderEmail,
                TemplateVariable::MESSAGE => nl2br($values->message, false),
            ],
            false
        );

        $this->onSave();
    }
}
