<?php

namespace App\AdminModule\CMSModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\CMS\News;
use App\Model\CMS\NewsRepository;
use Nette;
use Nette\Application\UI\Form;

class NewsForm extends Nette\Object
{
    /** @var News */
    private $news;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var NewsRepository */
    private $newsRepository;

    public function __construct(BaseForm $baseFormFactory, NewsRepository $newsRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->newsRepository = $newsRepository;
    }

    public function create($id)
    {
        $this->news = $this->newsRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addDateTimePicker('published', 'admin.cms.news_published')
            ->addRule(Form::FILLED, 'admin.cms.news_published_empty');

        $form->addTextArea('text', 'admin.cms.news_text')
            ->addRule(Form::FILLED, 'admin.cms.news_text_empty')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');

        if ($this->news) {
            $form->setDefaults([
                'id' => $id,
                'published' => $this->news->getPublished(),
                'text' => $this->news->getText()
            ]);
        }
        else {
            $form->setDefaults([
                'published' => new \DateTime()
            ]);
        }

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values) {
        if (!$this->news)
            $this->news = new News();

        $this->news->setText($values['text']);
        $this->news->setPublished($values['published']);

        $this->newsRepository->save($this->news);
    }
}
