<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Cms\Faq;
use App\Model\Cms\FaqRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Contributte\ReCaptcha\Forms\ReCaptchaField;
use Contributte\ReCaptcha\ReCaptchaProvider;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use stdClass;
use Tracy\Debugger;

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

    public function __construct(
        BaseFormFactory $baseFormFactory,
        UserRepository $userRepository,
        ReCaptchaProvider $recaptchaProvider
    ) {
        $this->baseFormFactory   = $baseFormFactory;
        $this->userRepository    = $userRepository;
        $this->recaptchaProvider = $recaptchaProvider;
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
            ->addRule(Form::FILLED, 'web.contact_form_content.email_empty');

        $form->addTextArea('message', 'web.contact_form_content.message')
            ->addRule(Form::FILLED, 'web.contact_form_content.message_empty');

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
//        $faq = new Faq();
//
//        $faq->setQuestion($values->question);
//        $faq->setAuthor($this->user);
//
//        $this->faqRepository->save($faq);

        $this->onSave();
    }
}
