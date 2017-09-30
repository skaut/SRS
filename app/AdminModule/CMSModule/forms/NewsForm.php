<?php

namespace App\AdminModule\CMSModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\CMS\News;
use App\Model\CMS\NewsRepository;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro úpravu aktuality.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class NewsForm extends Nette\Object
{
    /**
     * Upravovaná aktualita.
     * @var News
     */
    private $news;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var NewsRepository */
    private $newsRepository;


    /**
     * NewsForm constructor.
     * @param BaseForm $baseFormFactory
     * @param NewsRepository $newsRepository
     */
    public function __construct(BaseForm $baseFormFactory, NewsRepository $newsRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->newsRepository = $newsRepository;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @return Form
     */
    public function create($id)
    {
        $this->news = $this->newsRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addDateTimePicker('published', 'admin.cms.news_published')
            ->addRule(Form::FILLED, 'admin.cms.news_published_empty');

        $form->addCheckbox('pinned', 'admin.cms.news_edit_pinned');

        $form->addTextArea('text', 'admin.cms.news_text')
            ->addRule(Form::FILLED, 'admin.cms.news_text_empty')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setAttribute('class', 'btn btn-warning');

        if ($this->news) {
            $form->setDefaults([
                'id' => $id,
                'published' => $this->news->getPublished(),
                'pinned' => $this->news->isPinned(),
                'text' => $this->news->getText()
            ]);
        } else {
            $form->setDefaults([
                'published' => new \DateTime()
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
     */
    public function processForm(Form $form, \stdClass $values)
    {
        if (!$form['cancel']->isSubmittedBy()) {
            if (!$this->news)
                $this->news = new News();

            $this->news->setText($values['text']);
            $this->news->setPublished($values['published']);
            $this->news->setPinned($values['pinned']);

            $this->newsRepository->save($this->news);
        }
    }
}
