<?php

namespace App\AdminModule\CMSModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\CMS\Faq;
use App\Model\CMS\FaqRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro úpravu otázky.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class FaqForm
{
    use Nette\SmartObject;
    
    /**
     * Upravovaná otázka.
     * @var Faq
     */
    private $faq;

    /**
     * Přihlášený uživatel.
     * @var  User
     */
    private $user;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var FaqRepository */
    private $faqRepository;

    /** @var UserRepository */
    private $userRepository;


    /**
     * FaqForm constructor.
     * @param BaseForm $baseFormFactory
     * @param FaqRepository $faqRepository
     * @param UserRepository $userRepository
     */
    public function __construct(BaseForm $baseFormFactory, FaqRepository $faqRepository, UserRepository $userRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->faqRepository = $faqRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @param $userId
     * @return Form
     */
    public function create($id, $userId)
    {
        $this->faq = $this->faqRepository->findById($id);
        $this->user = $this->userRepository->findById($userId);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addTextArea('question', 'admin.cms.faq_question')
            ->addRule(Form::FILLED, 'admin.cms.faq_question_empty');

        $form->addTextArea('answer', 'admin.cms.faq_answer')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addCheckbox('public', 'admin.cms.faq_public_form');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setAttribute('class', 'btn btn-warning');

        if ($this->faq) {
            $form->setDefaults([
                'id' => $id,
                'question' => $this->faq->getQuestion(),
                'answer' => $this->faq->getAnswer(),
                'public' => $this->faq->isPublic()
            ]);
        } else {
            $form->setDefaults([
                'public' => TRUE
            ]);
        }

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function processForm(Form $form, \stdClass $values)
    {
        if (!$form['cancel']->isSubmittedBy()) {
            if (!$this->faq) {
                $this->faq = new Faq();
                $this->faq->setAuthor($this->user);
            }

            $this->faq->setQuestion($values['question']);
            $this->faq->setAnswer($values['answer']);
            $this->faq->setPublic($values['public']);

            $this->faqRepository->save($this->faq);
        }
    }
}
