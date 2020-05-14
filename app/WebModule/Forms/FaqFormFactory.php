<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Cms\Faq;
use App\Model\Cms\FaqRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;
use stdClass;

/**
 * Formulář pro položení otázky.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class FaqFormFactory
{
    use Nette\SmartObject;

    /**
     * Přihlášený uživatel.
     */
    private User $user;

    private BaseFormFactory $baseFormFactory;

    private FaqRepository $faqRepository;

    private UserRepository $userRepository;

    public function __construct(BaseFormFactory $baseFormFactory, FaqRepository $faqRepository, UserRepository $userRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->faqRepository   = $faqRepository;
        $this->userRepository  = $userRepository;
    }

    /**
     * Vytvoří formulář.
     */
    public function create(int $id) : Form
    {
        $this->user = $this->userRepository->findById($id);

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
     * @throws ORMException
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        $faq = new Faq();

        $faq->setQuestion($values->question);
        $faq->setAuthor($this->user);

        $this->faqRepository->save($faq);
    }
}
