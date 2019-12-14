<?php

declare(strict_types=1);

namespace App\AdminModule\CMSModule\Presenters;

use App\AdminModule\CMSModule\Components\IPagesGridControlFactory;
use App\AdminModule\CMSModule\Components\PagesGridControl;
use App\AdminModule\CMSModule\Forms\IPageFormFactory;
use App\AdminModule\CMSModule\Forms\PageForm;
use App\Model\CMS\Content\Content;
use App\Model\CMS\PageRepository;

/**
 * Presenter starající se o správu stránek.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PagesPresenter extends CMSBasePresenter
{
    /**
     * @var IPagesGridControlFactory
     * @inject
     */
    public $pagesGridControlFactory;

    /**
     * @var IPageFormFactory
     * @inject
     */
    public $pageFormFactory;

    /**
     * @var PageRepository
     * @inject
     */
    public $pagesRepository;


    public function renderContent(int $id, string $area) : void
    {
        $page = $this->pagesRepository->findById($id);

        $this->template->page = $page;
        $this->template->id   = $id;
        $this->template->area = $area;
    }

    protected function createComponentPagesGrid() : PagesGridControl
    {
        return $this->pagesGridControlFactory->create();
    }

    protected function createComponentPageForm() : PageForm
    {
        $id   = (int) $this->getParameter('id');
        $area = $this->getParameter('area');

        $control = $this->pageFormFactory->create($id, $area);

        $control->onPageSave[] = function (PageForm $control, $submitName) : void {
            $this->flashMessage('admin.cms.pages_content_saved', 'success');

            switch ($submitName) {
                case 'submitAndContinue':
                case 'submitAdd':
                    $this->redirect('Pages:content', ['id' => $control->id, 'area' => $control->area]);
                case 'submitMain':
                    $this->redirect('Pages:content', ['id' => $control->id, 'area' => Content::MAIN]);
                case 'submitSidebar':
                    $this->redirect('Pages:content', ['id' => $control->id, 'area' => Content::SIDEBAR]);
                default:
                    $this->redirect('Pages:default');
            }
        };

        $control->onPageSaveError[] = function (PageForm $control) : void {
            $this->flashMessage('admin.cms.pages_content_save_error', 'danger');
            $this->redirect('Pages:content', ['id' => $control->id, 'area' => $control->area]);
        };

        return $control;
    }
}
