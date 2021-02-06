<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Cms\Faq;
use App\Model\Cms\Repositories\FaqRepository;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;
use stdClass;

/**
 * Formulář pro úpravu otázky.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class FaqFormFactory
{
    use Nette\SmartObject;

    /**
     * Upravovaná otázka.
     */
    private ?Faq $faq = null;

    /**
     * Přihlášený uživatel.
     */
    private ?User $user = null;

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
    public function create(?int $id, int $userId): Form
    {
        $this->faq  = $id === null ? null : $this->faqRepository->findById($id);
        $this->user = $this->userRepository->findById($userId);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addTextArea('question', 'admin.cms.faq.common.question')
            ->addRule(Form::FILLED, 'admin.cms.faq.form.question_empty');

        $form->addTextArea('answer', 'admin.cms.faq.form.answer')
            ->setHtmlAttribute('class', 'tinymce-paragraph');

        $form->addCheckbox('public', 'admin.cms.faq.form.public');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-warning');

        if ($this->faq) {
            $form->setDefaults([
                'id' => $id,
                'question' => $this->faq->getQuestion(),
                'answer' => $this->faq->getAnswer(),
                'public' => $this->faq->isPublic(),
            ]);
        } else {
            $form->setDefaults(['public' => true]);
        }

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    public function processForm(Form $form, stdClass $values): void
    {
        if ($form->isSubmitted() === $form['cancel']) {
            return;
        }

        if (! $this->faq) {
            $this->faq = new Faq();
            $this->faq->setAuthor($this->user);
        }

        $this->faq->setQuestion($values->question);
        $this->faq->setAnswer($values->answer);
        $this->faq->setPublic($values->public);

        $this->faqRepository->save($this->faq);
    }
}
