<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Presenters;

use App\AdminModule\CmsModule\Components\IPagesGridControlFactory;
use App\AdminModule\CmsModule\Components\PagesGridControl;
use App\AdminModule\CmsModule\Forms\IPageFormFactory;
use App\AdminModule\CmsModule\Forms\PageForm;
use App\Model\Cms\Content;
use App\Model\Cms\Repositories\PageRepository;
use Nette\DI\Attributes\Inject;

/**
 * Presenter starající se o správu stránek.
 */
class PagesPresenter extends CmsBasePresenter
{
    #[Inject]
    public IPagesGridControlFactory $pagesGridControlFactory;

    #[Inject]
    public IPageFormFactory $pageFormFactory;

    #[Inject]
    public PageRepository $pagesRepository;

    public function renderContent(int $id, string $area): void
    {
        $page = $this->pagesRepository->findById($id);

        $this->template->page = $page;
        $this->template->id   = $id;
        $this->template->area = $area;
    }

    protected function createComponentPagesGrid(): PagesGridControl
    {
        return $this->pagesGridControlFactory->create();
    }

    protected function createComponentPageForm(): PageForm
    {
        $id   = (int) $this->getParameter('id');
        $area = $this->getParameter('area');

        $control = $this->pageFormFactory->create($id, $area);

        $control->onPageSave[] = function (PageForm $control, $submitName): void {
            $this->flashMessage('admin.cms.pages.content.message.save_success', 'success');

            switch ($submitName) {
                case 'submitAndContinue':
                case 'submitAdd':
                    $this->redirect('Pages:content', ['id' => $control->id, 'area' => $control->area]);
                    // redirect
                case 'submitMain':
                    $this->redirect('Pages:content', ['id' => $control->id, 'area' => Content::MAIN]);
                    // redirect
                case 'submitSidebar':
                    $this->redirect('Pages:content', ['id' => $control->id, 'area' => Content::SIDEBAR]);
                    // redirect
                default:
                    $this->redirect('Pages:default');
            }
        };

        $control->onPageSaveError[] = function (PageForm $control): void {
            $this->flashMessage('admin.cms.pages.content.message.save_failed', 'danger');
            $this->redirect('Pages:content', ['id' => $control->id, 'area' => $control->area]);
        };

        return $control;
    }
}
