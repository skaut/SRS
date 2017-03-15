<?php

namespace App\AdminModule\CMSModule\Presenters;


use App\AdminModule\CMSModule\Components\INewsGridControlFactory;
use App\AdminModule\CMSModule\Forms\NewsForm;
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
     * @var NewsForm
     * @inject
     */
    public $newsFormFactory;

    /**
     * @var NewsRepository
     * @inject
     */
    public $newsRepository;

    public function renderEdit($id)
    {
    }

    protected function createComponentNewsGrid()
    {
        return $this->newsGridControlFactory->create();
    }

    protected function createComponentNewsForm()
    {
        $form = $this->newsFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($form['cancel']->isSubmittedBy())
                $this->redirect('News:default');

            $this->flashMessage('admin.cms.news_saved', 'success');

            if ($form['submitAndContinue']->isSubmittedBy()) {
                $id = $values['id'] ?: $this->newsRepository->findLastId();
                $this->redirect('News:edit', ['id' => $id]);
            } else
                $this->redirect('News:default');
        };

        return $form;
    }
}