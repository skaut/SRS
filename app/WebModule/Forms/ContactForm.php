<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Cms\Faq;
use App\Model\Cms\FaqRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
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

    /**
     * Událost při úspěšném neodeslání formuláře.
     *
     * @var callable[]
     */
    public array $onError = [];

    private BaseFormFactory $baseFormFactory;

    private UserRepository $userRepository;

    public function __construct(BaseFormFactory $baseFormFactory, UserRepository $userRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository  = $userRepository;
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
            $form->addReCaptcha('recaptcha', 'Captcha', 'Are you a bot?');
        }

        $form->addSubmit('submit', 'web.contact_form_content.send_message');

        if ($this->user !== null) {
            $emailText->setDisabled();

            $form->setDefaults([
                'email' => $this->user->getEmail(),
            ]);
        }

        $form->onSuccess[] = [$this, 'processForm'];

        $form->onError[] = function () use ($form) : void {
            $this->onError($form);
        };

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

        $this->onSave($this);
    }
}
