<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\MailService;
use Contributte\ReCaptcha\Forms\ReCaptchaField;
use Contributte\ReCaptcha\ReCaptchaProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use stdClass;

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

    public function __construct(
        BaseFormFactory $baseFormFactory,
        UserRepository $userRepository,
        ReCaptchaProvider $recaptchaProvider,
        MailService $mailService
    ) {
        $this->baseFormFactory   = $baseFormFactory;
        $this->userRepository    = $userRepository;
        $this->recaptchaProvider = $recaptchaProvider;
        $this->mailService       = $mailService;
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
            $emailText->setDisabled();

            $form->setDefaults([
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

        //todo: pridani prijemcu

        if ($values->sendCopy) {
            if ($this->user) {
                $recipientsUsers->add($this->user);
            } else {
                $recipientsEmails->add($values->email);
            }
        }

        $this->mailService->sendMailFromTemplate(
            $recipientsUsers,
            $recipientsEmails,
            Template::CONTACT_FORM,
            [
                TemplateVariable::SENDER => "", //todo
                TemplateVariable::MESSAGE => $values->message,
            ],
            false
        );

        $this->onSave();
    }
}
