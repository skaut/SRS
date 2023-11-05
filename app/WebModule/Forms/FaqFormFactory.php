<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Cms\Faq;
use App\Model\Cms\Repositories\FaqRepository;
use App\Model\User\User;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette;
use Nette\Application\UI\Form;
use stdClass;

/**
 * Formulář pro položení otázky.
 */
class FaqFormFactory
{
    use Nette\SmartObject;

    /**
     * Přihlášený uživatel.
     */
    private User|null $user = null;

    public function __construct(
        private readonly BaseFormFactory $baseFormFactory,
        private readonly FaqRepository $faqRepository,
    ) {
    }

    /**
     * Vytvoří formulář.
     */
    public function create(User|null $user): Form
    {
        $this->user = $user;

        $form = $this->baseFormFactory->create();

        $form->addTextArea('question', 'web.faq_content.question')
            ->addRule(Form::FILLED, 'web.faq_content.question_empty');

        $form->addSubmit('submit', 'web.faq_content.add_question');

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function processForm(Form $form, stdClass $values): void
    {
        $faq = new Faq();

        $faq->setQuestion($values->question);
        $faq->setAuthor($this->user);

        $this->faqRepository->save($faq);
    }
}
