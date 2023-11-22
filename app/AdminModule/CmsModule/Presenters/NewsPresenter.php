<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Presenters;

use App\AdminModule\CmsModule\Components\INewsGridControlFactory;
use App\AdminModule\CmsModule\Components\NewsGridControl;
use App\AdminModule\CmsModule\Forms\NewsFormFactory;
use App\Model\Cms\Repositories\NewsRepository;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use stdClass;

/**
 * Presenter starající se o správu aktualit.
 */
class NewsPresenter extends CmsBasePresenter
{
    #[Inject]
    public INewsGridControlFactory $newsGridControlFactory;

    #[Inject]
    public NewsFormFactory $newsFormFactory;

    #[Inject]
    public NewsRepository $newsRepository;

    public function renderEdit(int $id): void
    {
    }

    protected function createComponentNewsGrid(): NewsGridControl
    {
        return $this->newsGridControlFactory->create();
    }

    protected function createComponentNewsForm(): Form
    {
        $form = $this->newsFormFactory->create((int) $this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            if ($form->isSubmitted() === $form['cancel']) {
                $this->redirect('News:default');
            }

            $this->flashMessage('admin.cms.news.message.save_success', 'success');

            if ($form->isSubmitted() === $form['submitAndContinue']) {
                $id = $values->id ?: $this->newsRepository->findLastId(); // todo: nahradit
                $this->redirect('News:edit', ['id' => $id]);
            } else {
                $this->redirect('News:default');
            }
        };

        return $form;
    }
}
