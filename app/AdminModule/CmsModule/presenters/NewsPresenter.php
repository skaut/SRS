<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Presenters;

use App\AdminModule\CmsModule\Components\INewsGridControlFactory;
use App\AdminModule\CmsModule\Components\NewsGridControl;
use App\AdminModule\CmsModule\Forms\NewsFormFactory;
use App\AdminModule\Forms\BaseForm;
use App\Model\Cms\NewsRepository;
use stdClass;

/**
 * Presenter starající se o správu aktualit.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class NewsPresenter extends CmsBasePresenter
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

    public function renderEdit(int $id) : void
    {
    }

    protected function createComponentNewsGrid() : NewsGridControl
    {
        return $this->newsGridControlFactory->create();
    }

    protected function createComponentNewsForm() : BaseForm
    {
        $form = $this->newsFormFactory->create((int) $this->getParameter('id'));

        $form->onSuccess[] = function (BaseForm $form, stdClass $values) : void {
            if ($form->isSubmitted() === $form['cancel']) {
                $this->redirect('News:default');
            }

            $this->flashMessage('admin.cms.news_saved', 'success');

            if ($form->isSubmitted() === $form['submitAndContinue']) {
                $id = $values->id ?: $this->newsRepository->findLastId();
                $this->redirect('News:edit', ['id' => $id]);
            } else {
                $this->redirect('News:default');
            }
        };

        return $form;
    }
}
