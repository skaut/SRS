<?php

namespace App\AdminModule\CMSModule\Presenters;


use App\AdminModule\CMSModule\Components\INewsGridControlFactory;
use App\AdminModule\CMSModule\Forms\NewsFormFactory;
use App\Model\CMS\NewsRepository;
use Nette\Application\UI\Form;

class NewsPresenter extends CMSBasePresenter
{
    /**
     * @var INewsGridControlFactory
     * @inject
     */
    public $newsGridControlFactory;

    /**
     * @var NewsFormFactory
     * @inject
     */
    public $newsFormFactory;

    /**
     * @var NewsRepository
     * @inject
     */
    public $newsRepository;

    public function renderAdd() {
        $this['newsForm']->setDefaults([
            'published' => new \DateTime()
        ]);
    }

    public function renderEdit($id) {
        $news = $this->newsRepository->findNewsById($id);

        $this['newsForm']->setDefaults([
            'id' => $id,
            'published' => $news->getPublished(),
            'text' => $news->getText()
        ]);
    }

    protected function createComponentNewsGrid($name)
    {
        return $this->newsGridControlFactory->create($name);
    }

    protected function createComponentNewsForm($name)
    {
        $form = $this->newsFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($form['submitAndContinue']->isSubmittedBy()) {
                $news = $this->saveNews($values);
                $this->redirect('News:edit', ['id' => $news->getId()]);
            }
            else {
                $this->saveNews($values);
                $this->redirect('News:default');
            }
        };

        return $form;
    }

    private function saveNews($values) {
        $id = $values['id'];

        if ($id == null) {
            $news = $this->newsRepository->addNews($values['text'], $values['published']);
            $this->flashMessage('admin.cms.news_added', 'success');
        }
        else {
            $news = $this->newsRepository->editNews($values['id'], $values['text'], $values['published']);
            $this->flashMessage('admin.cms.news_edited', 'success');
        }

        return $news;
    }
}