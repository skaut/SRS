<?php

namespace App\AdminModule\CMSModule\Presenters;


use App\AdminModule\CMSModule\Components\IFaqGridControlFactory;
use App\AdminModule\CMSModule\Forms\FaqFormFactory;
use App\Model\CMS\FaqRepository;
use Nette\Application\UI\Form;

class FaqPresenter extends CMSBasePresenter
{
    /**
     * @var IFaqGridControlFactory
     * @inject
     */
    public $faqGridControlFactory;

    /**
     * @var FaqFormFactory
     * @inject
     */
    public $faqFormFactory;

    /**
     * @var FaqRepository
     * @inject
     */
    public $faqRepository;

    public function renderAdd() {
        $this['faqForm']->setDefaults([
            'public' => true
        ]);
    }

    public function renderEdit($id) {
        $question = $this->faqRepository->findQuestionById($id);

        $this['faqForm']->setDefaults([
            'id' => $id,
            'question' => $question->getQuestion(),
            'answer' => $question->getAnswer(),
            'public' => $question->isPublic()
        ]);
    }

    protected function createComponentFaqGrid($name)
    {
        return $this->faqGridControlFactory->create($name);
    }

    protected function createComponentFaqForm()
    {
        $form = $this->faqFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($form['submitAndContinue']->isSubmittedBy()) {
                $question = $this->saveQuestion($values);
                $this->redirect('Faq:edit', ['id' => $question->getId()]);
            }
            else {
                $this->saveQuestion($values);
                $this->redirect('Faq:default');
            }
        };

        return $form;
    }

    private function saveQuestion($values) {
        $id = $values['id'];

        if ($id == null) {
            $question = $this->faqRepository->addQuestion($this->dbuser, $values['question'], $values['answer'], $values['public']);
            $this->flashMessage('admin.cms.faq_added', 'success');
        }
        else {
            $question = $this->faqRepository->editQuestion($values['id'], $values['question'], $values['answer'], $values['public']);
            $this->flashMessage('admin.cms.faq_edited', 'success');
        }

        return $question;
    }
}