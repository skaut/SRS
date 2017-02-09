<?php

namespace App\AdminModule\CMSModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\CMS\Faq;
use App\Model\CMS\FaqRepository;
use App\Model\User\User;
use Nette\Application\UI\Form;
use Nette;

class FaqForm extends Nette\Object
{
    /** @var Faq */
    private $faq;

    /** @var  User */
    private $user;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var FaqRepository */
    private $faqRepository;


    public function __construct(BaseForm $baseFormFactory, FaqRepository $faqRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->faqRepository = $faqRepository;
    }

    public function create($id, $user)
    {
        $this->faq = $this->faqRepository->findById($id);
        $this->user = $user;

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addTextArea('question', 'admin.cms.faq_question')
            ->addRule(Form::FILLED, 'admin.cms.faq_question_empty');

        $form->addTextArea('answer', 'admin.cms.faq_answer')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addCheckbox('public', 'admin.cms.faq_public_form');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');

        if ($this->faq) {
            $form->setDefaults([
                'id' => $id,
                'question' => $this->faq->getQuestion(),
                'answer' => $this->faq->getAnswer(),
                'public' => $this->faq->isPublic()
            ]);
        }
        else {
            $form->setDefaults([
                'public' => true
            ]);
        }

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values) {
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
